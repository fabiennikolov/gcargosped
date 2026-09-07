<?php

namespace Tests\Feature;

use App\Models\Service;
use Illuminate\Contracts\Http\Kernel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;
use Tests\TestCase;

/**
 * Every page has to name one address as its own, and it has to be the same one
 * the sitemap and robots.txt point at — otherwise the site competes with itself
 * in Google over www, non-www and http.
 */
class CanonicalUrlTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config(['app.url' => 'https://gcargosped.com']);

        Service::create([
            'slug' => 'suhopten-transport',
            'title' => 'Сухопътен транспорт',
            'is_main' => true,
            'is_published' => true,
        ]);

        Service::create([
            'slug' => 'hladilni-prevozi',
            'title' => 'Хладилни превози',
            'is_main' => false,
            'is_published' => true,
        ]);
    }

    /** @return array<string, array{string, string}> */
    public static function pages(): array
    {
        return [
            'home' => ['/', 'https://gcargosped.com/'],
            'about' => ['/about', 'https://gcargosped.com/about'],
            'services' => ['/services', 'https://gcargosped.com/services'],
            'contact' => ['/contact', 'https://gcargosped.com/contact'],
            'blog' => ['/blog', 'https://gcargosped.com/blog'],
            'main service' => ['/service/suhopten-transport', 'https://gcargosped.com/service/suhopten-transport'],
            'sub service' => ['/sub-services/hladilni-prevozi', 'https://gcargosped.com/sub-services/hladilni-prevozi'],
        ];
    }

    #[DataProvider('pages')]
    public function test_every_page_canonicalises_to_its_own_address(string $url, string $canonical): void
    {
        $this->get($url)
            ->assertOk()
            ->assertSee('<link rel="canonical" href="'.$canonical.'">', escape: false);
    }

    public function test_the_host_the_visitor_typed_does_not_change_the_canonical(): void
    {
        // Reached over http, the page still names the https address: the tag is
        // built from APP_URL, not from the request, which is the whole reason
        // this is not url()->current(). The scheme is left to the web server —
        // behind a proxy that terminates TLS, Laravel can see http on a request
        // the browser made over https, and redirecting on that guess loops.
        $this->get('http://gcargosped.com/services')
            ->assertOk()
            ->assertSee('<link rel="canonical" href="https://gcargosped.com/services">', escape: false);
    }

    /** @return array<string, array{string, string}> */
    public static function wwwPages(): array
    {
        return [
            'home' => ['http://www.gcargosped.com/', 'https://gcargosped.com/'],
            'about' => ['https://www.gcargosped.com/about', 'https://gcargosped.com/about'],
            'service' => [
                'https://www.gcargosped.com/service/suhopten-transport',
                'https://gcargosped.com/service/suhopten-transport',
            ],
            'sitemap' => ['https://www.gcargosped.com/sitemap.xml', 'https://gcargosped.com/sitemap.xml'],
        ];
    }

    /**
     * The canonical tag is a hint Google is free to overrule, and did: it
     * indexed www.gcargosped.com/about over the address the page named. A 301
     * is not a hint — www stops being a page at all.
     */
    #[DataProvider('wwwPages')]
    public function test_www_is_redirected_to_the_bare_domain(string $url, string $target): void
    {
        $this->get($url)->assertStatus(301)->assertRedirect($target);
    }

    public function test_the_redirect_keeps_the_campaign_parameters(): void
    {
        // A 301 that drops utm_source deletes the campaign that paid for the
        // visit.
        $this->get('https://www.gcargosped.com/contact?utm_source=facebook&gclid=abc')
            ->assertRedirect('https://gcargosped.com/contact?utm_source=facebook&gclid=abc');
    }

    public function test_a_form_post_on_the_old_host_still_goes_through(): void
    {
        // A page still open at the www address must not lose what someone typed
        // into it: a 301 drops the body of a POST, so posts are left alone.
        $this->post('https://www.gcargosped.com/track/whatsapp', ['topic' => 'Хладилни превози'])
            ->assertNoContent();
    }

    public function test_a_host_that_is_not_www_is_left_alone(): void
    {
        // Staging, an IP, a preview URL and `php artisan serve` all run this
        // same code; sending them to production leaves the site testable
        // nowhere but production.
        $this->get('https://staging.gcargosped.com/about')->assertOk();
    }

    public function test_a_trailing_slash_is_not_a_second_address(): void
    {
        // public/.htaccess strips it, but nginx serves this site and never
        // reads that file — so /services/ answered 200 next to /services, with
        // nothing but the canonical tag to say they are one page.
        //
        // Sent through the kernel rather than $this->get(): the test helper
        // runs the address through url(), which quietly drops the trailing
        // slash and would make this pass without any code behind it.
        $response = $this->handle('http://localhost/services/?utm_source=facebook&gclid=abc');

        $this->assertSame(301, $response->getStatusCode());
        $this->assertSame(
            // The parameters keep the order they were written in, too.
            'http://localhost/services?utm_source=facebook&gclid=abc',
            $response->headers->get('Location'),
        );
    }

    /** Dispatches an address exactly as written, with nothing normalised. */
    private function handle(string $url): SymfonyResponse
    {
        return $this->app->make(Kernel::class)->handle(Request::create($url));
    }

    public function test_a_query_string_does_not_make_a_second_url(): void
    {
        $this->get('/services?utm_source=facebook')
            ->assertOk()
            ->assertSee('<link rel="canonical" href="https://gcargosped.com/services">', escape: false);
    }

    public function test_the_page_shares_the_same_address_it_declares(): void
    {
        // og:url is rendered from this prop; a share must not advertise an
        // address the page itself disowns.
        $this->get('/about')->assertInertia(
            fn ($page) => $page->where('canonical', 'https://gcargosped.com/about'),
        );
    }

    public function test_the_sitemap_lists_the_canonical_addresses(): void
    {
        $this->get('http://gcargosped.com/sitemap.xml')
            ->assertOk()
            ->assertSee('https://gcargosped.com/service/suhopten-transport')
            ->assertDontSee('http://gcargosped.com/service/suhopten-transport');
    }

    public function test_robots_points_at_the_sitemap_and_keeps_the_admin_out(): void
    {
        $this->get('/robots.txt')
            ->assertOk()
            ->assertSee('Sitemap: https://gcargosped.com/sitemap.xml')
            ->assertSee('Disallow: /admin');
    }
}
