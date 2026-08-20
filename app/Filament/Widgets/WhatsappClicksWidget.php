<?php

namespace App\Filament\Widgets;

use App\Models\WhatsappClick;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Carbon;

/**
 * How often the floating WhatsApp button was used, and for what.
 *
 * The chat itself happens on the office phone, so nothing downstream of the tap
 * is visible here — this is the whole measurement. The per-topic breakdown is
 * the useful half: it says which question keeps coming back.
 */
class WhatsappClicksWidget extends StatsOverviewWidget
{
    protected static ?int $sort = 3;

    protected function getStats(): array
    {
        $now = Carbon::now();
        $thisMonth = WhatsappClick::monthlyBreakdown($now);
        $lastMonth = array_sum(WhatsappClick::monthlyBreakdown($now->copy()->subMonthNoOverflow()));
        $total = array_sum($thisMonth);

        $topic = array_key_first($thisMonth);

        return [
            Stat::make('WhatsApp — този месец', (string) $total)
                ->description($this->trend($total, $lastMonth))
                ->color($total >= $lastMonth ? 'success' : 'gray'),

            Stat::make('Най-честа тема', $topic ?? '—')
                ->description($topic ? $thisMonth[$topic].' от '.$total : 'Още няма кликове'),

            Stat::make('Миналия месец', (string) $lastMonth)
                ->description('За сравнение'),
        ];
    }

    private function trend(int $current, int $previous): string
    {
        if ($previous === 0) {
            return $current > 0 ? 'Първи месец с кликове' : 'Няма кликове още';
        }

        $delta = (int) round((($current - $previous) / $previous) * 100);

        return ($delta >= 0 ? '+' : '').$delta.'% спрямо миналия месец';
    }
}
