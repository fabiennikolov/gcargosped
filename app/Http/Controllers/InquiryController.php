<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreInquiryRequest;
use App\Models\Inquiry;
use Illuminate\Http\RedirectResponse;

class InquiryController extends Controller
{
    public function store(StoreInquiryRequest $request): RedirectResponse
    {
        $inquiry = Inquiry::create([
            ...$request->safe()->except('website'),
            'ip' => $request->ip(),
        ]);

        return back()->with('inquiry', [
            'ok' => true,
            'id' => $inquiry->id,
            'message' => 'Благодарим! Ще се свържем с вас възможно най-скоро.',
        ]);
    }
}
