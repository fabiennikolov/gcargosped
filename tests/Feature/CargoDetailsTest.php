<?php

namespace Tests\Feature;

use App\Mail\InquiryReceived;
use App\Models\Inquiry;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class CargoDetailsTest extends TestCase
{
    use RefreshDatabase;

    /** @param array<string, mixed> $overrides */
    private function payload(array $overrides = []): array
    {
        return [
            'name' => 'Иван Петров',
            'phone' => '0888123456',
            'email' => 'ivan@example.com',
            'source' => 'contact',
            'cargo_quantity' => '4',
            'cargo_packaging' => 'Палети',
            'cargo_dimensions' => '120 × 80 × 150 см',
            'cargo_weight' => '1200 кг',
            'loading_date' => '2026-12-01',
            'frequency' => 'regular',
            ...$overrides,
        ];
    }

    public function test_it_stores_the_cargo_details(): void
    {
        Mail::fake();

        $this->post('/inquiries', $this->payload())->assertRedirect()->assertSessionHasNoErrors();

        $inquiry = Inquiry::sole();
        $this->assertSame(4, (int) $inquiry->cargo_quantity);
        $this->assertSame('Палети', $inquiry->cargo_packaging);
        $this->assertSame('120 × 80 × 150 см', $inquiry->cargo_dimensions);
        $this->assertSame('1200 кг', $inquiry->cargo_weight);
        $this->assertSame('2026-12-01', $inquiry->loading_date->toDateString());
        $this->assertSame('regular', $inquiry->frequency);
    }

    public function test_every_cargo_detail_is_optional(): void
    {
        Mail::fake();

        $this->post('/inquiries', [
            'name' => 'Иван', 'phone' => '0888123456', 'source' => 'offer',
        ])->assertSessionHasNoErrors();

        $this->assertNull(Inquiry::sole()->loading_date);
    }

    public function test_it_rejects_an_unknown_frequency_and_a_bad_quantity(): void
    {
        $this->post('/inquiries', $this->payload(['frequency' => 'weekly', 'cargo_quantity' => '0']))
            ->assertSessionHasErrors(['frequency', 'cargo_quantity']);

        $this->assertSame(0, Inquiry::count());
    }

    public function test_the_office_e_mail_lists_the_cargo_details(): void
    {
        Mail::fake();

        $this->post('/inquiries', $this->payload());

        Mail::assertSent(InquiryReceived::class, function (InquiryReceived $mail) {
            $html = $mail->render();

            return str_contains($html, 'Опаковка')
                && str_contains($html, 'Палети')
                && str_contains($html, '01.12.2026')
                && str_contains($html, 'Редовен');
        });
    }
}
