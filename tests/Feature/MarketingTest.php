<?php

namespace Tests\Feature;

use App\Filament\Pages\Marketing;
use App\Filament\Pages\SiteSettings;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * The tracking and verification codes under Маркетинг: rendered only in the
 * shape each vendor issues, tidied up from whatever was pasted, and never
 * reporting a lead on a page load.
 */
class MarketingTest extends TestCase
{
    use RefreshDatabase;

    private const GA4 = 'G-ABCDE12345';

    private const GTM = 'GTM-ABC1234';

    private const PIXEL = '1234567890123456';

    private const PIXEL_2 = '6543210987654321';

    private const LINKEDIN = '1234567';

    private const LINKEDIN_LEAD = '87654321';

    private const GOOGLE_VERIFICATION = 'aBcD_eFgH-1234567890xyz';

    private const FACEBOOK_VERIFICATION = 'abc123def456ghi789jkl0';

    protected function setUp(): void
    {
        parent::setUp();

        Setting::put('site_name', 'Глобал Карго Спед');
        Setting::put('phone', '0877 415 141');
        Setting::put('phone_raw', '+359877415141');
        Setting::put('email', 'gcargosped@gmail.com');
    }

    private function admin(): void
    {
        $this->actingAs(User::factory()->create(['is_admin' => true]));
    }

    /**
     * The libraries are fetched on the first interaction, not with the page,
     * so they never compete with the first paint; the stubs queue anything
     * sent before they arrive.
     */
    public function test_no_tag_library_is_fetched_with_the_page(): void
    {
        Setting::put('google_ads_id', 'AW-123456789');
        Setting::put('gtm_id', self::GTM);
        Setting::put('meta_pixel_ids', self::PIXEL);
        Setting::put('linkedin_partner_id', self::LINKEDIN);

        $this->get('/')
            ->assertOk()
            ->assertDontSee('<script async src="https://www.googletagmanager.com', false)
            ->assertSee("deferTagScript('https://connect.facebook.net/en_US/fbevents.js', true)", false)
            ->assertSee("deferTagScript('https://snap.licdn.com/li.lms-analytics/insight.min.js', true)", false)
            ->assertSee("window.addEventListener(e, load, { once: true, passive: true })", false);
    }

    /*
     * GDPR. Google's tags run under Consent Mode, denied until the visitor
     * accepts; the default must be on the page before any config or
     * container, or the first hits go out with cookies.
     */
    public function test_google_starts_with_consent_denied(): void
    {
        Setting::put('google_ads_id', 'AW-123456789');
        Setting::put('gtm_id', self::GTM);

        $html = $this->get('/')->assertOk()->getContent();

        $default = strpos($html, "gtag('consent', 'default'");
        $this->assertNotFalse($default);
        $this->assertStringContainsString("window.tagConsent === 'granted' ? 'granted' : 'denied'", $html);
        $this->assertLessThan(strpos($html, "gtag('config'"), $default);
        $this->assertLessThan(strpos($html, 'gtm.js?id='), $default);
    }

    /** Meta and LinkedIn have no consent mode: no consent, no library. */
    public function test_meta_and_linkedin_wait_for_consent(): void
    {
        Setting::put('meta_pixel_ids', self::PIXEL);
        Setting::put('linkedin_partner_id', self::LINKEDIN);

        $this->get('/')
            ->assertSee("deferTagScript('https://connect.facebook.net/en_US/fbevents.js', true)", false)
            ->assertSee("deferTagScript('https://snap.licdn.com/li.lms-analytics/insight.min.js', true)", false)
            ->assertSee("if (window.tagConsent === 'granted') gated.splice(0).forEach(inject);", false);
    }

    /** A visitor without JavaScript cannot consent, so is not tracked. */
    public function test_no_tracking_pixel_fires_without_javascript(): void
    {
        Setting::put('gtm_id', self::GTM);
        Setting::put('meta_pixel_ids', self::PIXEL);
        Setting::put('linkedin_partner_id', self::LINKEDIN);

        $this->get('/')
            ->assertDontSee('ns.html?id=', false)
            ->assertDontSee('facebook.com/tr?', false)
            ->assertDontSee('px.ads.linkedin.com', false);
    }

    /** The banner asks only when there is something to ask about. */
    #[DataProvider('trackingTags')]
    public function test_the_banner_is_required_by_any_tracking_tag(string $key, string $value): void
    {
        $this->get('/')->assertInertia(fn ($page) => $page->where('settings.tracking_consent_required', false));

        Setting::put($key, $value);

        $this->get('/')->assertInertia(fn ($page) => $page->where('settings.tracking_consent_required', true));
    }

    /** @return array<string, array{string, string}> */
    public static function trackingTags(): array
    {
        return [
            'google ads' => ['google_ads_id', 'AW-123456789'],
            'ga4' => ['ga4_id', self::GA4],
            'gtm' => ['gtm_id', self::GTM],
            'meta' => ['meta_pixel_ids', self::PIXEL],
            'linkedin' => ['linkedin_partner_id', self::LINKEDIN],
        ];
    }

    /** Verification tags set no cookie, so they ask for no consent. */
    public function test_verification_alone_needs_no_banner(): void
    {
        Setting::put('google_site_verification', self::GOOGLE_VERIFICATION);

        $this->get('/')
            ->assertDontSee('deferTagScript', false)
            ->assertInertia(fn ($page) => $page->where('settings.tracking_consent_required', false));
    }

    public function test_the_banner_text_and_privacy_link_are_editable(): void
    {
        $this->admin();

        Livewire::test(Marketing::class)
            ->fillForm(['consent_text' => 'Ползваме бисквитки.', 'privacy_url' => 'https://gcargosped.com/privacy'])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->get('/')->assertInertia(fn ($page) => $page
            ->where('settings.consent_text', 'Ползваме бисквитки.')
            ->where('settings.privacy_url', 'https://gcargosped.com/privacy'));
    }

    /** The link ends up in an href; javascript: is a URL too. */
    public function test_a_non_http_privacy_link_is_never_shared(): void
    {
        Setting::put('privacy_url', 'javascript:alert(1)');

        $this->get('/')->assertInertia(fn ($page) => $page->where('settings.privacy_url', null));
    }

    public function test_an_unconfigured_site_ships_no_tags(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertDontSee('googletagmanager.com', false)
            ->assertDontSee('fbevents.js', false)
            ->assertDontSee('snap.licdn.com', false)
            ->assertDontSee('deferTagScript', false)
            ->assertDontSee('site-verification', false)
            ->assertDontSee('domain-verification', false);
    }

    public function test_analytics_alone_loads_the_google_tag(): void
    {
        Setting::put('ga4_id', self::GA4);

        $this->get('/')
            ->assertSee('https://www.googletagmanager.com/gtag/js?id='.self::GA4, false)
            ->assertSee("gtag('config', '".self::GA4."')", false)
            ->assertInertia(fn ($page) => $page->where('settings.ga4_id', self::GA4));
    }

    /** One library, configured for both — not two copies of gtag.js. */
    public function test_ads_and_analytics_share_one_google_tag(): void
    {
        Setting::put('google_ads_id', 'AW-123456789');
        Setting::put('ga4_id', self::GA4);

        $html = $this->get('/')->assertOk()->getContent();

        $this->assertSame(1, substr_count($html, 'googletagmanager.com/gtag/js'));
        $this->assertStringContainsString("gtag('config', 'AW-123456789')", $html);
        $this->assertStringContainsString("gtag('config', '".self::GA4."')", $html);
    }

    public function test_tag_manager_is_loaded(): void
    {
        Setting::put('gtm_id', self::GTM);

        $this->get('/')
            ->assertSee("deferTagScript('https://www.googletagmanager.com/gtm.js?id=".self::GTM."')", false);
    }

    public function test_every_meta_pixel_is_initialised(): void
    {
        Setting::put('meta_pixel_ids', self::PIXEL."\n".self::PIXEL_2);

        $this->get('/')
            ->assertSee('fbevents.js', false)
            ->assertSee("fbq('init', '".self::PIXEL."')", false)
            ->assertSee("fbq('init', '".self::PIXEL_2."')", false)
            ->assertInertia(fn ($page) => $page->where('settings.meta_pixel_ids', [self::PIXEL, self::PIXEL_2]));
    }

    public function test_linkedin_is_loaded_and_its_lead_shared(): void
    {
        Setting::put('linkedin_partner_id', self::LINKEDIN);
        Setting::put('linkedin_lead_id', self::LINKEDIN_LEAD);

        $this->get('/')
            ->assertSee('_linkedin_partner_id = \''.self::LINKEDIN.'\'', false)
            ->assertInertia(fn ($page) => $page->where('settings.linkedin_lead_id', self::LINKEDIN_LEAD));
    }

    /** A conversion sent through a tag that never loaded goes nowhere. */
    public function test_a_linkedin_lead_without_the_tag_is_not_shared(): void
    {
        Setting::put('linkedin_lead_id', self::LINKEDIN_LEAD);

        $this->get('/')->assertInertia(fn ($page) => $page->where('settings.linkedin_lead_id', null));
    }

    public function test_the_verification_tags_are_in_the_head(): void
    {
        Setting::put('google_site_verification', self::GOOGLE_VERIFICATION);
        Setting::put('facebook_domain_verification', self::FACEBOOK_VERIFICATION);

        $this->get('/')
            ->assertSee('<meta name="google-site-verification" content="'.self::GOOGLE_VERIFICATION.'">', false)
            ->assertSee('<meta name="facebook-domain-verification" content="'.self::FACEBOOK_VERIFICATION.'">', false);
    }

    /** Every field ends up in the <head>, most inside a <script>. */
    #[DataProvider('malformedValues')]
    public function test_a_malformed_value_is_never_rendered(string $key, string $value, string $marker): void
    {
        Setting::put($key, $value);

        $this->get('/')
            ->assertOk()
            ->assertDontSee($marker, false)
            ->assertDontSee('alert(', false);
    }

    /** @return array<string, array{string, string, string}> */
    public static function malformedValues(): array
    {
        $xss = '"></script><script>alert(1)</script>';

        return [
            'ga4 with an ads id' => ['ga4_id', 'AW-123456789', 'googletagmanager.com'],
            'ga4 breaking out' => ['ga4_id', 'G-ABC'.$xss, 'googletagmanager.com'],
            'gtm lowercase garbage' => ['gtm_id', 'gtm-'.$xss, 'gtm.js'],
            'pixel with letters' => ['meta_pixel_ids', '12345abc'.$xss, 'fbevents.js'],
            'linkedin breaking out' => ['linkedin_partner_id', '123'.$xss, 'snap.licdn.com'],
            'google verification breaking out' => ['google_site_verification', 'abc'.$xss, 'google-site-verification'],
            'facebook verification breaking out' => ['facebook_domain_verification', 'abc'.$xss, 'facebook-domain-verification'],
        ];
    }

    /** Only the good ones in a mixed list are fired. */
    public function test_a_bad_pixel_does_not_take_the_good_ones_down(): void
    {
        Setting::put('meta_pixel_ids', self::PIXEL.", nonsense\n".self::PIXEL);

        $this->get('/')->assertInertia(fn ($page) => $page->where('settings.meta_pixel_ids', [self::PIXEL]));
    }

    public function test_a_page_load_is_never_a_lead(): void
    {
        Setting::put('google_ads_id', 'AW-123456789');
        Setting::put('google_ads_label', 'AbC-D_efGhIjKlM');
        Setting::put('ga4_id', self::GA4);
        Setting::put('gtm_id', self::GTM);
        Setting::put('meta_pixel_ids', self::PIXEL);
        Setting::put('linkedin_partner_id', self::LINKEDIN);
        Setting::put('linkedin_lead_id', self::LINKEDIN_LEAD);

        $this->get('/')
            ->assertOk()
            ->assertDontSee("gtag('event'", false)
            ->assertDontSee("'Lead'", false)
            ->assertDontSee("lintrk('track'", false)
            ->assertDontSee('inquiry_sent', false);
    }

    /** The raw tokens are for the template; the browser has no use for them. */
    public function test_verification_tokens_are_not_shared_as_props(): void
    {
        Setting::put('google_site_verification', self::GOOGLE_VERIFICATION);

        $this->get('/')->assertInertia(fn ($page) => $page->missing('settings.google_site_verification'));
    }

    /** Pasting the vendor's whole block has to be the whole job. */
    #[DataProvider('pastedSnippets')]
    public function test_the_admin_tidies_up_a_pasted_snippet(string $key, string $snippet, string $expected): void
    {
        $this->admin();

        Livewire::test(Marketing::class)
            ->set("data.{$key}", $snippet)
            ->assertSet("data.{$key}", $expected)
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertSame($expected, Setting::get($key));
    }

    /** @return array<string, array{string, string, string}> */
    public static function pastedSnippets(): array
    {
        return [
            'ga4 tag' => ['ga4_id', "<script async src=\"https://www.googletagmanager.com/gtag/js?id=G-ABCDE12345\"></script>\n<script>gtag('config', 'G-ABCDE12345');</script>", self::GA4],
            'gtm head block' => ['gtm_id', "<!-- Google Tag Manager -->\n<script>(function(w,d,s,l,i){…})(window,document,'script','dataLayer','GTM-ABC1234');</script>", self::GTM],
            'meta base code' => ['meta_pixel_ids', "<script>!function(f,b,e,v,n,t,s){…}(window, document,'script','https://connect.facebook.net/en_US/fbevents.js');\nfbq('init', '1234567890123456');\nfbq('track', 'PageView');</script>\n<noscript><img src=\"https://www.facebook.com/tr?id=1234567890123456&ev=PageView&noscript=1\"/></noscript>", self::PIXEL],
            'two pixels with commas' => ['meta_pixel_ids', self::PIXEL.', '.self::PIXEL_2, self::PIXEL."\n".self::PIXEL_2],
            'linkedin insight tag' => ['linkedin_partner_id', "<script type=\"text/javascript\">\n_linkedin_partner_id = \"1234567\";\n…</script>", self::LINKEDIN],
            'linkedin event code' => ['linkedin_lead_id', "window.lintrk('track', { conversion_id: 87654321 });", self::LINKEDIN_LEAD],
            'google verification tag' => ['google_site_verification', '<meta name="google-site-verification" content="'.self::GOOGLE_VERIFICATION.'" />', self::GOOGLE_VERIFICATION],
            'google dns record' => ['google_site_verification', 'google-site-verification='.self::GOOGLE_VERIFICATION, self::GOOGLE_VERIFICATION],
            'facebook verification tag' => ['facebook_domain_verification', '<meta name="facebook-domain-verification" content="'.self::FACEBOOK_VERIFICATION.'" />', self::FACEBOOK_VERIFICATION],
        ];
    }

    /** A typo that silently stops the tracking is worse than a red field. */
    #[DataProvider('unsaveable')]
    public function test_a_malformed_value_cannot_be_saved(string $key, string $value): void
    {
        $this->admin();

        Livewire::test(Marketing::class)
            ->fillForm([$key => $value])
            ->call('save')
            ->assertHasFormErrors([$key]);
    }

    /** @return array<string, array{string, string}> */
    public static function unsaveable(): array
    {
        return [
            'ga4' => ['ga4_id', 'UA-12345-1'],
            'gtm' => ['gtm_id', 'GTM ABC'],
            'pixel' => ['meta_pixel_ids', 'my pixel'],
            'linkedin' => ['linkedin_partner_id', 'abc'],
        ];
    }

    public function test_the_page_may_be_saved_empty(): void
    {
        $this->admin();

        Livewire::test(Marketing::class)->call('save')->assertHasNoFormErrors();
    }

    /** Moving the Ads fields to their own page must not let the old one wipe them. */
    public function test_saving_the_site_settings_leaves_the_marketing_codes_alone(): void
    {
        Setting::put('google_ads_id', 'AW-123456789');
        Setting::put('meta_pixel_ids', self::PIXEL);

        $this->admin();

        Livewire::test(SiteSettings::class)->call('save')->assertHasNoFormErrors();

        $this->assertSame('AW-123456789', Setting::get('google_ads_id'));
        $this->assertSame(self::PIXEL, Setting::get('meta_pixel_ids'));
    }

    public function test_the_admin_page_opens(): void
    {
        $this->admin();

        $this->get(Marketing::getUrl())->assertOk()->assertSee('Meta (Facebook / Instagram) Pixel');
    }
}
