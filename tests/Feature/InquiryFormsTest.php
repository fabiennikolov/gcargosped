<?php

namespace Tests\Feature;

use App\Filament\Pages\InquiryFormsPage;
use App\Mail\InquiryReceived;
use App\Models\Inquiry;
use App\Models\Setting;
use App\Models\User;
use App\Support\InquiryForms;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Inertia\Testing\AssertableInertia;
use Livewire\Livewire;
use Tests\TestCase;

class InquiryFormsTest extends TestCase
{
    use RefreshDatabase;

    /** @param array<int, array<string, mixed>> $rows */
    private function saveOffer(array $rows): void
    {
        Setting::put('form_offer', json_encode(InquiryForms::normalize($rows), JSON_UNESCAPED_UNICODE));
    }

    private function customKey(string $label): string
    {
        $row = collect(InquiryForms::rows('offer'))->firstWhere('label', $label);

        return 'x'.$row['id'];
    }

    public function test_untouched_forms_are_the_original_ones(): void
    {
        $this->get('/contact')->assertInertia(fn (AssertableInertia $page) => $page
            ->where('forms.offer.fields.0.key', 'name')
            ->where('forms.offer.fields.12.key', 'message')
            ->count('forms.offer.fields', 13)
            ->count('forms.lead.fields', 3)
            ->where('forms.lead.button', null));
    }

    public function test_a_new_field_is_shown_validated_stored_and_mailed(): void
    {
        Mail::fake();

        $this->saveOffer([
            ['field' => 'name'],
            ['field' => 'phone'],
            ['field' => 'custom', 'type' => 'text', 'label' => 'Фирма', 'required' => true],
            ['field' => 'custom', 'type' => 'select', 'label' => 'ADR', 'options' => ['Да', 'Не']],
            ['field' => 'custom', 'type' => 'checkbox', 'label' => 'Нужна е застраховка'],
        ]);

        $company = $this->customKey('Фирма');
        $adr = $this->customKey('ADR');
        $insurance = $this->customKey('Нужна е застраховка');

        $this->get('/contact')->assertInertia(fn (AssertableInertia $page) => $page
            ->where('forms.offer.fields.2.label', 'Фирма')
            ->where('forms.offer.fields.2.key', $company));

        $base = ['name' => 'Иван', 'phone' => '0888123456', 'source' => 'contact'];

        $this->post('/inquiries', $base)->assertSessionHasErrors("extra.$company");
        $this->post('/inquiries', [...$base, 'extra' => [$company => 'Х', $adr => 'Може би']])
            ->assertSessionHasErrors("extra.$adr");

        $this->post('/inquiries', [...$base, 'extra' => [$company => 'Транс ООД', $adr => 'Да', $insurance => true]])
            ->assertSessionHasNoErrors();

        $this->assertSame([
            ['label' => 'Фирма', 'value' => 'Транс ООД'],
            ['label' => 'ADR', 'value' => 'Да'],
            ['label' => 'Нужна е застраховка', 'value' => 'Да'],
        ], Inquiry::sole()->extra);

        Mail::assertSent(InquiryReceived::class, fn (InquiryReceived $mail) => str_contains($mail->render(), 'Транс ООД'));
    }

    public function test_a_removed_standard_field_is_not_stored(): void
    {
        Mail::fake();
        $this->saveOffer([['field' => 'name'], ['field' => 'phone']]);

        $this->post('/inquiries', [
            'name' => 'Иван', 'phone' => '0888123456', 'source' => 'contact', 'cargo_weight' => '5 т',
        ])->assertSessionHasNoErrors();

        $this->assertNull(Inquiry::sole()->cargo_weight);
    }

    public function test_a_standard_field_can_be_made_required(): void
    {
        $this->saveOffer([['field' => 'name'], ['field' => 'phone'], ['field' => 'loading_date', 'required' => true]]);

        $this->post('/inquiries', ['name' => 'Иван', 'phone' => '0888', 'source' => 'contact'])
            ->assertSessionHasErrors('loading_date');
    }

    public function test_name_and_phone_cannot_be_removed_or_made_optional(): void
    {
        $rows = InquiryForms::normalize([['field' => 'phone', 'required' => false], ['field' => 'email']]);

        $this->assertSame(['name', 'phone', 'email'], array_column($rows, 'field'));
        $this->assertTrue($rows[1]['required']);
    }

    public function test_the_admin_page_saves_the_forms(): void
    {
        $this->actingAs(User::factory()->create(['is_admin' => true]));

        $this->get('/admin/forms')->assertOk()->assertSee('Форми за запитване');

        Livewire::test(InquiryFormsPage::class)
            ->set('data.offer', [
                'a' => ['field' => 'name', 'label' => 'Вашето име', 'required' => true],
                'b' => ['field' => 'phone', 'label' => 'Телефон', 'required' => true],
                'c' => ['field' => 'custom', 'type' => 'number', 'label' => 'Брой курсове месечно'],
            ])
            ->set('data.offer_button', 'Поискай оферта')
            ->call('save')
            ->assertHasNoErrors();

        $offer = InquiryForms::forFrontend('offer');
        $this->assertSame(['Вашето име', 'Телефон', 'Брой курсове месечно'], array_column($offer['fields'], 'label'));
        $this->assertSame('Поискай оферта', $offer['button']);
        $this->assertCount(3, InquiryForms::forFrontend('lead')['fields']);
    }
}
