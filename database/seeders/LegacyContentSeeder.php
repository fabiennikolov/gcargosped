<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Partner;
use App\Models\Post;
use App\Models\Service;
use App\Models\Setting;
use App\Support\Slug;
use Illuminate\Database\Seeder;

/**
 * Imports the content that used to be hard-coded in the static index.html.
 * Idempotent — re-running updates rows in place instead of duplicating them,
 * so it is safe to run again after a content tweak upstream.
 */
class LegacyContentSeeder extends Seeder
{
    public function run(): void
    {
        $path = database_path('data/legacy-content.json');

        if (! is_file($path)) {
            $this->command->error("Missing {$path} — run the extractor first.");

            return;
        }

        $data = json_decode(file_get_contents($path), true, 512, JSON_THROW_ON_ERROR);

        $this->seedSettings($data['settings']);
        $this->seedServices($data);
        $this->seedPartners($data['partners']);
        $this->seedPosts($data['posts']);
    }

    private function seedSettings(array $content): void
    {
        $settings = [
            'site_name' => 'Глобал Карго Спед',
            'hero_title' => $content['heroTitle'],
            'hero_subtitle' => $content['heroSub'],
            'hero_cta' => $content['heroCta'],
            'phone' => $content['phone'],
            'phone_raw' => $content['phoneRaw'],
            'email' => $content['email'],
            'address' => 'Пловдив, ул. Ламартин 2',
            'working_hours' => 'Понеделник – Петък, 09:00 – 18:00',
            'facebook_url' => '',
            'linkedin_url' => '',
            'seo_title' => 'Глобал Карго Спед — Транспорт, спедиция и логистика | България, Европа, Турция',
            'seo_description' => 'Сухопътен и хладилен транспорт, групажни и експресни превози, '
                .'складиране и логистика в България, Европа и Турция.',
        ];

        foreach ($settings as $key => $value) {
            Setting::updateOrCreate(['key' => $key], ['value' => $value]);
        }

        $this->command->info('Settings: '.count($settings));
    }

    private function seedServices(array $data): void
    {
        $pages = $data['servicePages'];
        $count = 0;

        // The three headline services keep the /service/{slug} prefix.
        foreach ($data['mainServices'] as $i => $svc) {
            $this->upsertService($svc['slug'], $pages[$svc['slug']], true, $i, null);
            $count++;
        }

        // The seventeen specialised ones keep /sub-services/{slug}.
        foreach ($data['subServices'] as $i => $svc) {
            $this->upsertService($svc['slug'], $pages[$svc['slug']], false, $i, $svc['ic'] ?? null);
            $count++;
        }

        $this->command->info("Services: {$count}");
    }

    private function upsertService(string $slug, array $page, bool $isMain, int $order, ?string $icon): void
    {
        Service::updateOrCreate(
            ['slug' => $slug],
            [
                'title' => $page['title'],
                'subtitle' => $page['sub'] ?? null,
                'body' => $page['body'] ?? null,
                // The static site shipped local WebP for every slug and only
                // fell back to the Webflow CDN; we take the local copy so the
                // new site has no dependency on Webflow at all.
                'image' => "assets/img/services/{$slug}.webp",
                'icon_svg' => $icon,
                'is_main' => $isMain,
                'sort_order' => $order,
                'is_published' => true,
            ],
        );
    }

    private function seedPartners(array $partners): void
    {
        foreach ($partners as $i => $partner) {
            // Keyed on the logo path, not the name: five of the fourteen logos
            // carry no company name in the source data, so keying on name would
            // collapse all five into a single row.
            Partner::updateOrCreate(
                ['logo' => $partner['d']],
                [
                    'name' => $partner['n'] ?: 'Партньор '.($i + 1),
                    'sort_order' => $i,
                    'is_published' => true,
                ],
            );
        }

        $this->command->info('Partners: '.Partner::count());
    }

    private function seedPosts(array $posts): void
    {
        foreach ($posts as $i => $post) {
            $category = Category::updateOrCreate(
                ['slug' => Slug::make($post['cat'], 'category-'.($i + 1))],
                ['name' => $post['cat'], 'sort_order' => $i],
            );

            $slug = Slug::make($post['t'], 'post-'.($i + 1));

            Post::updateOrCreate(
                ['slug' => $slug],
                [
                    'title' => $post['t'],
                    'excerpt' => $post['d'] ?? null,
                    // The static site only ever had teasers, never article
                    // bodies — the excerpt stands in until someone writes the
                    // real post in the admin.
                    'body' => $post['d'] ?? null,
                    'category_id' => $category->id,
                    'read_minutes' => (int) filter_var($post['read'] ?? '3', FILTER_SANITIZE_NUMBER_INT) ?: 3,
                    'published_at' => now()->subDays(($i + 1) * 7),
                    'is_published' => true,
                ],
            );
        }

        $this->command->info('Posts: '.count($posts));
    }
}
