<?php

namespace Tests\Feature;

use App\Filament\Pages\SiteSettings;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * The wording around the inquiry form belongs to the client, not to us: it
 * carries a promise about how quickly the office answers, and that promise
 * changes with the season. Hard-coded, changing it is a deploy.
 */
class InquiryCopyTest extends TestCase
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

    public function test_it_shares_the_form_copy_with_every_page(): void
    {
        Setting::put('inquiry_title', 'Искате оферта?');
        Setting::put('inquiry_subtitle', 'Оставете данни и ще ви потърсим.');

        $this->get('/')
            ->assertSuccessful()
            ->assertInertia(fn ($page) => $page
                ->where('settings.inquiry_title', 'Искате оферта?')
                ->where('settings.inquiry_subtitle', 'Оставете данни и ще ви потърсим.')
            );
    }

    public function test_the_thank_you_message_is_the_one_the_admin_wrote(): void
    {
        Setting::put('inquiry_success', 'Получихме запитването. До скоро!');

        $this->submit()->assertSessionHas('inquiry.message', 'Получихме запитването. До скоро!');
    }

    /*
     * A cleared field must not leave the visitor with a thank-you card that has
     * a heading and nothing under it.
     */
    public function test_a_blank_thank_you_falls_back_to_the_built_in_one(): void
    {
        Setting::put('inquiry_success', '   ');

        $this->submit()->assertSessionHas('inquiry.message', 'Благодарим! Ще се свържем с вас възможно най-скоро.');
    }

    public function test_it_thanks_the_visitor_before_the_copy_is_ever_configured(): void
    {
        $this->submit()->assertSessionHas('inquiry.message', 'Благодарим! Ще се свържем с вас възможно най-скоро.');
    }

    public function test_the_admin_can_rewrite_the_copy_from_the_settings_page(): void
    {
        $this->actingAs(User::factory()->create(['is_admin' => true]));

        Livewire::test(SiteSettings::class)
            ->fillForm([
                'inquiry_title' => 'Поискай оферта',
                'inquiry_subtitle' => 'Пишете ни — отговаряме в работно време.',
                'inquiry_success' => 'Благодарим! Ще ви потърсим.',
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertSame('Поискай оферта', Setting::get('inquiry_title'));
        $this->assertSame('Пишете ни — отговаряме в работно време.', Setting::get('inquiry_subtitle'));
        $this->assertSame('Благодарим! Ще ви потърсим.', Setting::get('inquiry_success'));
    }

    private function submit(): TestResponse
    {
        return $this->post('/inquiries', [
            'name' => 'Иван Петров',
            'phone' => '0888123456',
            'source' => 'offer',
        ])->assertRedirect();
    }
}
