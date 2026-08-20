<?php

namespace Tests\Feature;

use App\Filament\Pages\SiteSettings;
use App\Models\Setting;
use App\Models\User;
use App\Models\WhatsappClick;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Livewire\Livewire;
use Tests\TestCase;

class WhatsappButtonTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Setting::put('site_name', 'Глобал Карго Спед');
        Setting::put('phone', '0877 415 141');
        Setting::put('phone_raw', '+359877415141');
        Setting::put('email', 'gcargosped@gmail.com');
    }

    public function test_it_shares_the_whatsapp_settings_with_every_page(): void
    {
        Setting::put('whatsapp_number', '+359877415141');
        Setting::put('whatsapp_topics', "Оферта за превоз\nПроследяване на товар");

        $this->get('/')
            ->assertSuccessful()
            ->assertInertia(fn ($page) => $page
                ->where('settings.whatsapp_number', '+359877415141')
                ->where('settings.whatsapp_topics', "Оферта за превоз\nПроследяване на товар")
            );
    }

    /*
     * The number is filled in after launch, so an unconfigured site must ship a
     * page with no button rather than a button that opens an empty chat.
     */
    public function test_the_number_is_absent_until_it_is_configured(): void
    {
        // Setting::map() only carries rows that exist, so an unconfigured number
        // reaches the component as undefined rather than an empty string.
        $this->get('/')
            ->assertSuccessful()
            ->assertInertia(fn ($page) => $page->missing('settings.whatsapp_number'));
    }

    public function test_it_records_a_click_with_its_topic_and_page(): void
    {
        $this->postJson('/track/whatsapp', [
            'topic' => 'Оферта за превоз',
            'page' => '/service/suhopten-transport',
        ])->assertNoContent();

        $click = WhatsappClick::sole();

        $this->assertSame('Оферта за превоз', $click->topic);
        $this->assertSame('/service/suhopten-transport', $click->page);
    }

    /*
     * sendBeacon cannot set the CSRF header, so a token-protected endpoint
     * would 419 on every real click while still passing any test that posts
     * through the testing helpers. This asserts the exemption directly.
     */
    public function test_a_click_without_a_csrf_token_is_accepted(): void
    {
        $this->withMiddleware(ValidateCsrfToken::class)
            ->post('/track/whatsapp', ['topic' => 'Оферта за превоз'])
            ->assertNoContent();

        $this->assertSame(1, WhatsappClick::count());
    }

    public function test_it_rejects_a_click_with_no_topic(): void
    {
        $this->postJson('/track/whatsapp', ['page' => '/'])
            ->assertUnprocessable();

        $this->assertSame(0, WhatsappClick::count());
    }

    /*
     * The endpoint is unauthenticated and free to call, so the monthly figure is
     * only worth reporting to the client if a flood cannot inflate it.
     */
    public function test_it_throttles_repeated_clicks(): void
    {
        for ($i = 0; $i < 20; $i++) {
            $this->postJson('/track/whatsapp', ['topic' => 'Друго'])->assertNoContent();
        }

        $this->postJson('/track/whatsapp', ['topic' => 'Друго'])->assertStatus(429);
    }

    /*
     * These go through load → save rather than asserting on the repeater's form
     * state: in simple mode Filament keys its rows by generated UUID internally
     * and only flattens on dehydration, so asserting that shape would test
     * Filament rather than this page. What matters is what lands in the table.
     */
    public function test_it_stores_the_menu_options_as_a_json_array(): void
    {
        Setting::put('whatsapp_topics', '["Оферта за превоз","Проследяване на товар"]');

        $this->saveSettingsPage();

        $this->assertSame(
            '["Оферта за превоз","Проследяване на товар"]',
            Setting::get('whatsapp_topics'),
        );
    }

    /*
     * The field was a free-text textarea before it became a repeater, so a site
     * that has not re-saved its settings must keep its options — and blank lines
     * in that text must not become empty buttons in the menu.
     */
    public function test_it_migrates_the_legacy_newline_separated_options(): void
    {
        Setting::put('whatsapp_topics', "Оферта за превоз\n\nПроследяване на товар\n");

        $this->saveSettingsPage();

        $this->assertSame(
            '["Оферта за превоз","Проследяване на товар"]',
            Setting::get('whatsapp_topics'),
        );
    }

    /*
     * An empty list must clear the setting rather than store "[]", so the menu
     * falls back to its defaults instead of rendering with no options at all.
     */
    public function test_clearing_every_option_stores_nothing(): void
    {
        $this->saveSettingsPage();

        $this->assertNull(Setting::get('whatsapp_topics'));
    }

    /** Open the settings page as an admin and save it unchanged. */
    private function saveSettingsPage(): void
    {
        $this->actingAs(User::factory()->create(['is_admin' => true]));

        Livewire::test(SiteSettings::class)
            ->call('save')
            ->assertHasNoFormErrors();
    }

    public function test_the_monthly_breakdown_ranks_topics_and_ignores_other_months(): void
    {
        $now = Carbon::now();

        WhatsappClick::create(['topic' => 'Оферта за превоз', 'created_at' => $now]);
        WhatsappClick::create(['topic' => 'Оферта за превоз', 'created_at' => $now]);
        WhatsappClick::create(['topic' => 'Друго', 'created_at' => $now]);
        WhatsappClick::create(['topic' => 'Оферта за превоз', 'created_at' => $now->copy()->subMonthNoOverflow()]);

        $this->assertSame(
            ['Оферта за превоз' => 2, 'Друго' => 1],
            WhatsappClick::monthlyBreakdown($now),
        );
    }
}
