<?php

namespace Tests\Feature;

use App\Filament\Pages\SiteSettings;
use App\Models\Service;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * The pieces of the public site the office asked to be able to change without
 * a developer: the figures in the dark band, the logo, the line in the footer
 * and the ALT text under each service photo.
 */
class EditableContentTest extends TestCase
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

    public function test_the_home_page_figures_come_from_the_settings(): void
    {
        Setting::put('stats', '[{"value":"16+","label":"години опит"},{"value":"7","label":"държави"}]');

        $this->get('/')->assertInertia(
            fn ($page) => $page->where(
                'settings.stats',
                '[{"value":"16+","label":"години опит"},{"value":"7","label":"държави"}]',
            ),
        );
    }

    public function test_the_figures_are_stored_as_rows_and_survive_a_save(): void
    {
        Setting::put('stats', '[{"value":"16+","label":"години опит"}]');

        $this->saveSettingsPage();

        $this->assertSame('[{"value":"16+","label":"години опит"}]', Setting::get('stats'));
    }

    /** A half-filled row would render a number with no caption, or the reverse. */
    public function test_a_row_missing_a_field_cannot_be_saved(): void
    {
        Setting::put('stats', '[{"value":"16+","label":"години опит"}]');

        $this->actingAs(User::factory()->create(['is_admin' => true]));

        Livewire::test(SiteSettings::class)
            ->set('data.stats', [['value' => '7', 'label' => '']])
            ->call('save')
            ->assertHasFormErrors();

        // The stored figures are left exactly as they were.
        $this->assertSame('[{"value":"16+","label":"години опит"}]', Setting::get('stats'));
    }

    /** Emptying the list falls back to the figures built into the page. */
    public function test_clearing_every_figure_stores_nothing(): void
    {
        Setting::put('stats', '[{"value":"16+","label":"години опит"}]');

        $this->actingAs(User::factory()->create(['is_admin' => true]));

        Livewire::test(SiteSettings::class)
            ->set('data.stats', [])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertNull(Setting::get('stats'));
    }

    public function test_the_footer_line_is_editable(): void
    {
        Setting::put('footer_tagline', 'Вашият товар е наша грижа.');

        $this->get('/')->assertInertia(
            fn ($page) => $page->where('settings.footer_tagline', 'Вашият товар е наша грижа.'),
        );
    }

    public function test_an_uploaded_logo_reaches_the_page_as_a_url(): void
    {
        Storage::fake('public');

        $this->actingAs(User::factory()->create(['is_admin' => true]));

        Livewire::test(SiteSettings::class)
            ->set('data.logo_path', [UploadedFile::fake()->image('logo.png')])
            ->call('save')
            ->assertHasNoFormErrors();

        $stored = Setting::get('logo_path');
        $this->assertNotNull($stored);
        Storage::disk('public')->assertExists($stored);

        $this->get('/')->assertInertia(
            fn ($page) => $page->where('settings.logo_url', Storage::disk('public')->url($stored)),
        );
    }

    /** No logo uploaded yet means the file the site shipped with, not a gap. */
    public function test_no_uploaded_logo_leaves_the_url_empty_for_the_fallback(): void
    {
        $this->get('/')->assertInertia(
            fn ($page) => $page->where('settings.logo_url', null),
        );
    }

    public function test_a_service_carries_its_alt_text_to_the_page(): void
    {
        $service = Service::create([
            'slug' => 'hladilni-prevozi',
            'title' => 'Хладилни превози',
            'image' => 'assets/img/services/hladilni-prevozi.webp',
            'image_alt' => 'Хладилен камион зарежда палети с храни',
            'is_main' => false,
            'is_published' => true,
        ]);

        $this->get($service->url)->assertInertia(
            fn ($page) => $page->where('service.imageAlt', 'Хладилен камион зарежда палети с храни'),
        );

        $this->get('/services')->assertInertia(
            fn ($page) => $page->where('services.data.0.imageAlt', 'Хладилен камион зарежда палети с храни'),
        );
    }

    /** Open the settings page as an admin and save it unchanged. */
    private function saveSettingsPage(): void
    {
        $this->actingAs(User::factory()->create(['is_admin' => true]));

        Livewire::test(SiteSettings::class)
            ->call('save')
            ->assertHasNoFormErrors();
    }
}
