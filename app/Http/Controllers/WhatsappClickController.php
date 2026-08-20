<?php

namespace App\Http\Controllers;

use App\Models\WhatsappClick;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class WhatsappClickController extends Controller
{
    /**
     * Record a tap on the floating WhatsApp button.
     *
     * Unauthenticated and fire-and-forget: the browser sends this while it is
     * already navigating to wa.me, so it must never block or fail visibly.
     * Throttled at the route because it costs nothing to call and a flood of
     * forged taps would make the monthly figure worthless.
     */
    public function __invoke(Request $request): Response
    {
        $data = $request->validate([
            'topic' => ['required', 'string', 'max:120'],
            'page' => ['nullable', 'string', 'max:255'],
        ]);

        WhatsappClick::create([
            ...$data,
            'ip' => $request->ip(),
        ]);

        return response()->noContent();
    }
}
