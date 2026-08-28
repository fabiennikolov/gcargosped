<?php

namespace Tests\Feature;

use App\Filament\Pages\SiteSettings;
use App\Models\Service;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * Google Ads has to be able to tell a visitor from a lead.
 *
 * The tag itself belongs on every page — that is what lets Ads recognise the
 * click it paid for when the visitor comes back days later. The conversion is
 * a different thing entirely, and is reported only when somebody actually
 * sends a form. A conversion action wired to a page load counts every reader
 * of the home page as an inquiry, and the campaign is then optimised towards
 * traffic that never asks for anything.
 */
class GoogleAdsTest extends TestCase
{
    use RefreshDatabase;

    private const ID = 'AW-123456789';

    private const LABEL = 'AbC-D_efGhIjKlM';

    protected function setUp(): void
    {
        parent::setUp();

        Setting::put('site_name', 'Глобал Карго Спед');
        Setting::put('phone', '0877 415 141');
        Setting::put('phone_raw', '+359877415141');
        Setting::put('email', 'gcargosped@gmail.com');

        Service::create([
            'slug' => 'suhopten-transport',
            'title' => 'Сухопътен транспорт',
            'is_main' => true,
            'is_published' => true,
        ]);
    }

    /** @return array<string, array{string}> */
    public static function publicUrls(): array
    {
        return [
            'home' => ['/'],
            'about' => ['/about'],
            'services' => ['/services'],
            'service' => ['/service/suhopten-transport'],
            'contact' => ['/contact'],
            'blog' => ['/blog'],
        ];
    }

    #[DataProvider('publicUrls')]
    public function test_the_tag_is_on_every_page(string $url): void
    {
        Setting::put('google_ads_id', self::ID);

        $this->get($url)
            ->assertOk()
            ->assertSee('https://www.googletagmanager.com/gtag/js?id='.self::ID, false)
            ->assertSee("gtag('config', '".self::ID."')", false);
    }

    /*
     * The account is connected after launch, so an unconfigured site must ship
     * pages with no tag rather than one pointing at nothing.
     */
    public function test_there_is_no_tag_until_an_account_is_configured(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertDontSee('googletagmanager.com', false);
    }

    /**
     * The ID is typed by hand into a field whose value is rendered inside a
     * <script>. Anything that is not the shape Google issues is treated as no
     * tag at all.
     */
    #[DataProvider('malformedIds')]
    public function test_a_malformed_id_is_never_rendered(string $id): void
    {
        Setting::put('google_ads_id', $id);

        $this->get('/')
            ->assertOk()
            ->assertDontSee('googletagmanager.com', false)
            ->assertDontSee('alert(', false);
    }

    /** @return array<string, array{string}> */
    public static function malformedIds(): array
    {
        return [
            'empty' => [''],
            'no prefix' => ['123456789'],
            'wrong product' => ['G-ABCDE12345'],
            'query appended' => ['AW-123456789&foo=bar'],
            'closes the script' => ['AW-123"></script><script>alert(1)</script>'],
        ];
    }

    public function test_the_conversion_target_is_shared_with_every_page(): void
    {
        Setting::put('google_ads_id', self::ID);
        Setting::put('google_ads_label', self::LABEL);

        $this->get('/')->assertInertia(fn ($page) => $page
            ->where('settings.google_ads_id', self::ID)
            ->where('settings.google_ads_send_to', self::ID.'/'.self::LABEL)
        );
    }

    /*
     * What is in the clipboard of somebody copying send_to out of the Ads
     * event snippet is the whole "AW-…/label", not the half we ask for.
     */
    public function test_the_label_may_be_pasted_whole_out_of_the_event_snippet(): void
    {
        Setting::put('google_ads_id', self::ID);
        Setting::put('google_ads_label', self::ID.'/'.self::LABEL);

        $this->get('/')->assertInertia(
            fn ($page) => $page->where('settings.google_ads_send_to', self::ID.'/'.self::LABEL),
        );
    }

    /*
     * Half a configuration is not a conversion: an event naming no conversion
     * is counted as nothing, so nothing is sent until the label is there. The
     * tag itself still loads — remarketing works without a conversion.
     */
    public function test_nothing_is_reported_until_the_label_is_set(): void
    {
        Setting::put('google_ads_id', self::ID);

        $this->get('/')
            ->assertSee('googletagmanager.com', false)
            ->assertInertia(fn ($page) => $page->where('settings.google_ads_send_to', null));
    }

    public function test_a_label_alone_is_not_a_tag(): void
    {
        Setting::put('google_ads_label', self::LABEL);

        $this->get('/')
            ->assertDontSee('googletagmanager.com', false)
            ->assertInertia(fn ($page) => $page->where('settings.google_ads_send_to', null));
    }

    /**
     * The point of the whole arrangement: the pages carry the tag, and not one
     * of them carries a conversion. It is sent from the form, in the browser,
     * when the inquiry has already been saved.
     */
    #[DataProvider('publicUrls')]
    public function test_a_page_load_is_never_a_conversion(string $url): void
    {
        Setting::put('google_ads_id', self::ID);
        Setting::put('google_ads_label', self::LABEL);

        $response = $this->get($url)->assertOk();

        // The props do carry the conversion's address — the browser needs it
        // to report the lead later — but nothing on the page sends anything to
        // it. Only the form does, and only once it has a thank-you to show.
        $response->assertDontSee("gtag('event'", false);
        $response->assertDontSee('conversion', false);
    }

    /*
     * The point of the two fields being forgiving: whoever has the codes on
     * screen has one string, "AW-…/etikеt", and pasting it into the first box
     * has to be the whole job.
     */
    public function test_one_paste_into_the_id_field_configures_both_halves(): void
    {
        Setting::put('google_ads_id', self::ID.'/'.self::LABEL);

        $this->get('/')
            ->assertOk()
            // The tag still loads with the account alone; the label belongs to
            // the conversion, not to the library.
            ->assertSee('https://www.googletagmanager.com/gtag/js?id='.self::ID.'"', false)
            ->assertInertia(fn ($page) => $page
                ->where('settings.google_ads_id', self::ID)
                ->where('settings.google_ads_send_to', self::ID.'/'.self::LABEL)
            );
    }

    /** A label typed into its own box wins over one that came along with the ID. */
    public function test_the_label_field_wins_over_the_half_behind_the_slash(): void
    {
        Setting::put('google_ads_id', self::ID.'/OldLabel');
        Setting::put('google_ads_label', self::LABEL);

        $this->get('/')->assertInertia(
            fn ($page) => $page->where('settings.google_ads_send_to', self::ID.'/'.self::LABEL),
        );
    }

    /**
     * What Google actually puts on the screen is a block of markup, and that
     * is what gets copied. The field tidies it up instead of refusing it.
     */
    public function test_the_admin_tidies_up_a_pasted_event_snippet(): void
    {
        $this->actingAs(User::factory()->create(['is_admin' => true]));

        $snippet = "<!-- Event snippet -->\n<script>\n  gtag('event', 'conversion', {'send_to': '".self::ID.'/'.self::LABEL."'});\n</script>";

        Livewire::test(SiteSettings::class)
            ->set('data.google_ads_id', $snippet)
            ->assertSet('data.google_ads_id', self::ID)
            ->assertSet('data.google_ads_label', self::LABEL)
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertSame(self::ID, Setting::get('google_ads_id'));
        $this->assertSame(self::LABEL, Setting::get('google_ads_label'));
    }

    /** The global tag block names the account and no conversion. */
    public function test_the_admin_tidies_up_a_pasted_google_tag(): void
    {
        $this->actingAs(User::factory()->create(['is_admin' => true]));

        $snippet = '<script async src="https://www.googletagmanager.com/gtag/js?id='.self::ID.'"></script>';

        Livewire::test(SiteSettings::class)
            ->set('data.google_ads_id', $snippet)
            ->assertSet('data.google_ads_id', self::ID)
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertSame(self::ID, Setting::get('google_ads_id'));
    }

    /** Pasted into the wrong box, it still ends up in the right ones. */
    public function test_a_send_to_pasted_into_the_label_field_fills_the_id_too(): void
    {
        $this->actingAs(User::factory()->create(['is_admin' => true]));

        Livewire::test(SiteSettings::class)
            ->set('data.google_ads_label', self::ID.'/'.self::LABEL)
            ->assertSet('data.google_ads_id', self::ID)
            ->assertSet('data.google_ads_label', self::LABEL)
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertSame(self::ID, Setting::get('google_ads_id'));
        $this->assertSame(self::LABEL, Setting::get('google_ads_label'));
    }

    public function test_the_admin_can_connect_the_ads_account(): void
    {
        $this->actingAs(User::factory()->create(['is_admin' => true]));

        Livewire::test(SiteSettings::class)
            ->fillForm(['google_ads_id' => self::ID, 'google_ads_label' => self::LABEL])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertSame(self::ID, Setting::get('google_ads_id'));
        $this->assertSame(self::LABEL, Setting::get('google_ads_label'));
    }

    /** A typo that silently stops the tracking is worse than a red field. */
    public function test_a_malformed_id_cannot_be_saved(): void
    {
        $this->actingAs(User::factory()->create(['is_admin' => true]));

        Livewire::test(SiteSettings::class)
            ->fillForm(['google_ads_id' => 'AW 123456789'])
            ->call('save')
            ->assertHasFormErrors(['google_ads_id']);
    }

    /** The section is opened long after launch, so blank has to stay valid. */
    public function test_the_section_may_be_left_empty(): void
    {
        $this->actingAs(User::factory()->create(['is_admin' => true]));

        Livewire::test(SiteSettings::class)
            ->fillForm(['google_ads_id' => '', 'google_ads_label' => ''])
            ->call('save')
            ->assertHasNoFormErrors();
    }
}
