<?php

namespace Tests\Feature;

use App\Models\Service;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
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

        config(['app.url' => 'https://www.gcargosped.com']);

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
            'home' => ['/', 'https://www.gcargosped.com/'],
            'about' => ['/about', 'https://www.gcargosped.com/about'],
            'services' => ['/services', 'https://www.gcargosped.com/services'],
            'contact' => ['/contact', 'https://www.gcargosped.com/contact'],
            'blog' => ['/blog', 'https://www.gcargosped.com/blog'],
            'main service' => ['/service/suhopten-transport', 'https://www.gcargosped.com/service/suhopten-transport'],
            'sub service' => ['/sub-services/hladilni-prevozi', 'https://www.gcargosped.com/sub-services/hladilni-prevozi'],
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
        // The same page reached over the bare domain still points at the www
        // one, which is the whole reason this is not url()->current().
        $this->get('http://gcargosped.com/services')
            ->assertOk()
            ->assertSee('<link rel="canonical" href="https://www.gcargosped.com/services">', escape: false);
    }

    public function test_a_query_string_and_a_trailing_slash_do_not_make_a_second_url(): void
    {
        $this->get('/services/?utm_source=facebook')
            ->assertOk()
            ->assertSee('<link rel="canonical" href="https://www.gcargosped.com/services">', escape: false);
    }

    public function test_the_page_shares_the_same_address_it_declares(): void
    {
        // og:url is rendered from this prop; a share must not advertise an
        // address the page itself disowns.
        $this->get('/about')->assertInertia(
            fn ($page) => $page->where('canonical', 'https://www.gcargosped.com/about'),
        );
    }

    public function test_the_sitemap_lists_the_canonical_addresses(): void
    {
        $this->get('http://gcargosped.com/sitemap.xml')
            ->assertOk()
            ->assertSee('https://www.gcargosped.com/service/suhopten-transport')
            ->assertDontSee('http://gcargosped.com/service/suhopten-transport');
    }

    public function test_robots_points_at_the_sitemap_and_keeps_the_admin_out(): void
    {
        $this->get('/robots.txt')
            ->assertOk()
            ->assertSee('Sitemap: https://www.gcargosped.com/sitemap.xml')
            ->assertSee('Disallow: /admin');
    }
}
