<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * One row per tap on the floating WhatsApp button.
 *
 * The conversation itself happens in WhatsApp, on the owner's phone, where we
 * can see nothing — so this counter is the only measure of what the button is
 * worth. It is also the evidence for whether the repeated questions are worth
 * automating later.
 */
class WhatsappClick extends Model
{
    protected $guarded = [];

    /**
     * Clicks per topic for a calendar month, busiest first.
     *
     * @return array<string, int>
     */
    public static function monthlyBreakdown(?Carbon $month = null): array
    {
        $month ??= Carbon::now();

        return static::query()
            ->whereBetween('created_at', [$month->copy()->startOfMonth(), $month->copy()->endOfMonth()])
            ->selectRaw('topic, count(*) as total')
            ->groupBy('topic')
            ->orderByDesc('total')
            ->pluck('total', 'topic')
            ->all();
    }
}
