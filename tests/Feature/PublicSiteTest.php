<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Inquiry;
use App\Models\Post;
use App\Models\Service;
use App\Models\Setting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class PublicSiteTest extends TestCase
{
    use RefreshDatabase;

    private Service $main;

    private Service $sub;

    protected function setUp(): void
    {
        parent::setUp();

        Setting::put('site_name', 'Глобал Карго Спед');
        Setting::put('phone', '0877 415 141');
        Setting::put('phone_raw', '+359877415141');
        Setting::put('email', 'gcargosped@gmail.com');

        $this->main = Service::create([
            'slug' => 'suhopten-transport',
            'title' => 'Сухопътен транспорт',
            'subtitle' => 'В цяла Европа',
            'body' => "Първи абзац.\n\nВтори абзац.",
            'is_main' => true,
            'is_published' => true,
        ]);

        $this->sub = Service::create([
            'slug' => 'hladilni-prevozi',
            'title' => 'Хладилни превози',
            'is_main' => false,
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
            'contact' => ['/contact'],
            'blog' => ['/blog'],
        ];
    }

    #[DataProvider('publicUrls')]
    public function test_it_serves_the_pages_the_old_site_had_indexed(string $url): void
    {
        $this->get($url)->assertOk();
    }

    public function test_it_keeps_the_legacy_service_url_shape(): void
    {
        $this->get('/service/suhopten-transport')->assertOk();
        $this->get('/sub-services/hladilni-prevozi')->assertOk();
    }

    public function test_it_refuses_to_serve_a_service_under_the_wrong_prefix(): void
    {
        // Both prefixes resolving one record would be duplicate content.
        $this->get('/service/hladilni-prevozi')->assertNotFound();
        $this->get('/sub-services/suhopten-transport')->assertNotFound();
    }

    public function test_it_hides_unpublished_services(): void
    {
        $this->sub->update(['is_published' => false]);

        $this->get('/sub-services/hladilni-prevozi')->assertNotFound();
    }

    public function test_it_hides_draft_and_scheduled_posts(): void
    {
        $category = Category::create(['slug' => 'saveti', 'name' => 'Съвети']);

        $draft = Post::create([
            'slug' => 'draft', 'title' => 'Чернова', 'category_id' => $category->id,
            'is_published' => false, 'published_at' => now()->subDay(),
        ]);

        $scheduled = Post::create([
            'slug' => 'scheduled', 'title' => 'Насрочена', 'category_id' => $category->id,
            'is_published' => true, 'published_at' => now()->addWeek(),
        ]);

        $live = Post::create([
            'slug' => 'live', 'title' => 'Публикувана', 'category_id' => $category->id,
            'is_published' => true, 'published_at' => now()->subHour(),
        ]);

        $this->get("/blog/{$draft->slug}")->assertNotFound();
        $this->get("/blog/{$scheduled->slug}")->assertNotFound();
        $this->get("/blog/{$live->slug}")->assertOk();
    }

    public function test_it_lists_only_live_urls_in_the_sitemap(): void
    {
        $this->sub->update(['is_published' => false]);

        $this->get('/sitemap.xml')
            ->assertOk()
            ->assertHeader('Content-Type', 'application/xml')
            ->assertSee('/service/suhopten-transport')
            ->assertDontSee('/sub-services/hladilni-prevozi');
    }

    public function test_it_stores_an_enquiry_from_the_contact_form(): void
    {
        $this->post('/inquiries', [
            'name' => 'Иван Петров',
            'phone' => '0888123456',
            'email' => 'ivan@example.com',
            'cargo_type' => 'Хладилни превози',
            'message' => 'Палети за Германия.',
            'source' => 'contact',
        ])->assertRedirect();

        $this->assertDatabaseHas('inquiries', [
            'name' => 'Иван Петров',
            'source' => 'contact',
            'status' => 'new',
        ]);
    }

    public function test_it_rejects_a_submission_that_fills_the_honeypot(): void
    {
        $this->post('/inquiries', [
            'name' => 'Bot',
            'phone' => '123',
            'source' => 'offer',
            'website' => 'http://spam.example',
        ])->assertSessionHasErrors('website');

        $this->assertSame(0, Inquiry::count());
    }

    public function test_it_requires_a_name_and_a_phone_number(): void
    {
        $this->post('/inquiries', ['source' => 'offer'])
            ->assertSessionHasErrors(['name', 'phone']);

        $this->assertSame(0, Inquiry::count());
    }

    public function test_it_shares_editable_contact_details_with_every_page(): void
    {
        Setting::put('phone', '0700 12 345');

        $this->get('/')->assertInertia(
            fn (AssertableInertia $page) => $page->where('settings.phone', '0700 12 345'),
        );
    }

    public function test_the_home_page_carries_every_service_headline_first(): void
    {
        // The marquee takes one list, ordered so the headline services lead.
        $this->get('/')->assertInertia(
            fn (AssertableInertia $page) => $page
                ->component('site/home')
                ->has('services.data', 2)
                ->where('services.data.0.slug', 'suhopten-transport')
                ->where('services.data.1.slug', 'hladilni-prevozi'),
        );
    }
}
