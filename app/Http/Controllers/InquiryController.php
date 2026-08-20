<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreInquiryRequest;
use App\Mail\InquiryReceived;
use App\Models\Inquiry;
use App\Models\Setting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

class InquiryController extends Controller
{
    /** Shown when the admin has not written a thank-you of its own. */
    private const DEFAULT_THANK_YOU = 'Благодарим! Ще се свържем с вас възможно най-скоро.';

    public function store(StoreInquiryRequest $request): RedirectResponse
    {
        $inquiry = Inquiry::create([
            ...$request->safe()->except('website'),
            'ip' => $request->ip(),
        ]);

        $this->notifyOffice($inquiry);

        return back()->with('inquiry', [
            'ok' => true,
            'id' => $inquiry->id,
            // Editable in the admin, so the office can promise what it can
            // actually keep. Blank falls back rather than showing an empty
            // thank-you card.
            'message' => trim((string) Setting::get('inquiry_success')) ?: self::DEFAULT_THANK_YOU,
        ]);
    }

    /**
     * The inquiry is already safe in the database by the time this runs, so a
     * mail provider having a bad day must not turn a captured lead into an
     * error page — it is logged and the visitor still sees the thank-you.
     */
    private function notifyOffice(Inquiry $inquiry): void
    {
        try {
            Mail::to(config('mail.inquiries.to'))->send(new InquiryReceived($inquiry));
        } catch (Throwable $e) {
            Log::error('Inquiry notification failed', [
                'inquiry_id' => $inquiry->id,
                'exception' => $e,
            ]);
        }
    }
}
