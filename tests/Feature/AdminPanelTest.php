<?php

namespace Tests\Feature;

use App\Filament\Resources\Inquiries\InquiryResource;
use App\Models\Inquiry;
use App\Models\Service;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class AdminPanelTest extends TestCase
{
    use RefreshDatabase;

    /** @return array<string, array{string}> */
    public static function adminUrls(): array
    {
        return [
            'dashboard' => ['/admin'],
            'services' => ['/admin/services'],
            'posts' => ['/admin/posts'],
            'categories' => ['/admin/categories'],
            'inquiries' => ['/admin/inquiries'],
            'partners' => ['/admin/partners'],
            'settings' => ['/admin/site-settings'],
        ];
    }

    #[DataProvider('adminUrls')]
    public function test_it_keeps_the_admin_panel_behind_a_login(string $url): void
    {
        $this->get($url)->assertRedirect();
    }

    #[DataProvider('adminUrls')]
    public function test_it_opens_every_admin_screen_for_a_signed_in_user(string $url): void
    {
        $this->actingAs(User::factory()->create(['is_admin' => true]))->get($url)->assertOk();
    }

    public function test_it_opens_the_edit_screen_of_a_real_record(): void
    {
        $service = Service::create([
            'slug' => 'suhopten-transport',
            'title' => 'Сухопътен транспорт',
            'is_main' => true,
        ]);

        $this->actingAs(User::factory()->create(['is_admin' => true]))
            ->get("/admin/services/{$service->id}/edit")
            ->assertOk()
            ->assertSee('Сухопътен транспорт');
    }

    public function test_it_does_not_offer_creating_an_enquiry_by_hand(): void
    {
        // Enquiries only ever arrive through the public forms.
        $this->actingAs(User::factory()->create(['is_admin' => true]))
            ->get('/admin/inquiries/create')
            ->assertNotFound();
    }

    public function test_it_busts_the_settings_cache_when_a_value_is_saved(): void
    {
        Setting::put('phone', '0877 415 141');
        $this->assertSame('0877 415 141', Setting::get('phone'));

        Setting::put('phone', '0700 12 345');
        $this->assertSame('0700 12 345', Setting::get('phone'));
    }

    public function test_it_denies_the_panel_to_an_account_without_the_admin_flag(): void
    {
        // /register is public, so a plain account must not reach the admin.
        $this->actingAs(User::factory()->create(['is_admin' => false]))
            ->get('/admin')
            ->assertForbidden();
    }

    public function test_it_counts_unhandled_enquiries_in_the_navigation_badge(): void
    {
        Inquiry::create(['name' => 'A', 'phone' => '1', 'source' => 'offer', 'status' => 'new']);
        Inquiry::create(['name' => 'B', 'phone' => '2', 'source' => 'offer', 'status' => 'won']);

        $this->assertSame('1', InquiryResource::getNavigationBadge());
    }
}
