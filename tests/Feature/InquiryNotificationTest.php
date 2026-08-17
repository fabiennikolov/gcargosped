<?php

namespace Tests\Feature;

use App\Mail\InquiryReceived;
use App\Models\Inquiry;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class InquiryNotificationTest extends TestCase
{
    use RefreshDatabase;

    /** @param array<string, mixed> $overrides */
    private function submit(array $overrides = []): void
    {
        $this->post('/inquiries', [
            'name' => 'Иван Петров',
            'phone' => '0888123456',
            'email' => 'ivan@example.com',
            'cargo_type' => 'Палет',
            'origin' => 'София',
            'destination' => 'Мюнхен, Германия',
            'message' => 'Два палета, товарене в петък.',
            'source' => 'offer',
            ...$overrides,
        ])->assertRedirect();
    }

    public function test_it_mails_the_office_when_a_form_is_submitted(): void
    {
        Mail::fake();
        config(['mail.inquiries.to' => 'gcargosped@gmail.com']);

        $this->submit();

        Mail::assertSent(
            InquiryReceived::class,
            fn (InquiryReceived $mail) => $mail->hasTo('gcargosped@gmail.com')
                && $mail->inquiry->is(Inquiry::sole()),
        );
    }

    public function test_the_notification_replies_to_the_visitor(): void
    {
        Mail::fake();

        $this->submit();

        Mail::assertSent(
            InquiryReceived::class,
            fn (InquiryReceived $mail) => $mail->hasReplyTo('ivan@example.com'),
        );
    }

    public function test_it_omits_the_reply_to_when_no_e_mail_was_given(): void
    {
        Mail::fake();

        $this->submit(['email' => null]);

        Mail::assertSent(
            InquiryReceived::class,
            fn (InquiryReceived $mail) => $mail->envelope()->replyTo === [],
        );
    }

    public function test_it_renders_the_submitted_details(): void
    {
        $inquiry = Inquiry::create([
            'name' => 'Иван | Петров',
            'phone' => '0888123456',
            'email' => 'ivan@example.com',
            'cargo_type' => 'Палет',
            'origin' => 'София',
            'destination' => 'Мюнхен, Германия',
            'message' => 'Два палета, товарене в петък.',
            'source' => 'offer',
        ]);

        $rendered = (new InquiryReceived($inquiry))->render();

        // The pipe a visitor typed is escaped, so the Markdown table survives
        // it and every field still lands in its own row.
        $this->assertStringContainsString('<table', $rendered);
        $this->assertStringContainsString('Иван | Петров', $rendered);
        $this->assertStringContainsString('Мюнхен, Германия', $rendered);
        $this->assertStringContainsString('Два палета, товарене в петък.', $rendered);
        $this->assertStringContainsString('Поискай оферта', $rendered);
    }

    public function test_a_failing_mailer_does_not_lose_the_inquiry(): void
    {
        // Resend having a bad day must not turn a captured lead into a 500.
        Mail::shouldReceive('to->send')->andThrow(new \RuntimeException('Resend is down'));

        $this->submit();

        $this->assertDatabaseHas('inquiries', ['name' => 'Иван Петров']);
    }
}
