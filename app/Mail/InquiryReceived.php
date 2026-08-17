<?php

namespace App\Mail;

use App\Models\Inquiry;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * The notification the office gets whenever someone submits a form on the
 * public site. It is sent from the verified noreply@ sender, but replies go
 * straight back to whoever filled the form, so the inbox can be answered from.
 */
class InquiryReceived extends Mailable
{
    use SerializesModels;

    public function __construct(public Inquiry $inquiry) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: sprintf(
                'Ново запитване от %s (%s)',
                $this->inquiry->name,
                Inquiry::SOURCES[$this->inquiry->source] ?? $this->inquiry->source,
            ),
            // A reply-to is only useful when the form actually carried an
            // e-mail — the field is optional, a phone number is not.
            replyTo: $this->inquiry->email
                ? [new Address($this->inquiry->email, $this->inquiry->name)]
                : [],
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'mail.inquiry-received',
            with: [
                'rows' => $this->rows(),
                'adminUrl' => route('filament.admin.resources.inquiries.edit', ['record' => $this->inquiry]),
            ],
        );
    }

    /**
     * The detail table, built here rather than in the view so the pipe
     * characters a visitor could type never break the Markdown table.
     *
     * @return array<string, string>
     */
    private function rows(): array
    {
        $rows = [
            'Име' => $this->inquiry->name,
            'Телефон' => $this->inquiry->phone,
            'Имейл' => $this->inquiry->email,
            'Вид товар' => $this->inquiry->cargo_type,
            'От' => $this->inquiry->origin,
            'До' => $this->inquiry->destination,
            'Услуга' => $this->inquiry->service?->title,
            'Източник' => Inquiry::SOURCES[$this->inquiry->source] ?? $this->inquiry->source,
        ];

        return collect($rows)
            ->reject(fn (?string $value) => blank($value))
            ->map(fn (string $value) => str_replace(['|', "\n", "\r"], ['\|', ' ', ' '], $value))
            ->all();
    }
}
