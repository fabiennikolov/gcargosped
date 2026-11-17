<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Inquiry extends Model
{
    protected $guarded = [];

    public const STATUSES = [
        'new' => 'Ново',
        'in_progress' => 'В процес',
        'quoted' => 'Изпратена оферта',
        'won' => 'Спечелено',
        'closed' => 'Затворено',
    ];

    /** Which form the inquiry came from — mirrors the `source` values. */
    public const SOURCES = [
        'offer' => 'Поискай оферта',
        'contact' => 'Контакти',
        'service' => 'Страница на услуга',
    ];

    /** One-off or recurring — the office prices a regular line differently. */
    public const FREQUENCIES = [
        'one_time' => 'Еднократен',
        'regular' => 'Редовен',
    ];

    protected function casts(): array
    {
        return [
            'loading_date' => 'date',
            'extra' => 'array',
        ];
    }

    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }
}
