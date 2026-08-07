<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class Setting extends Model
{
    protected $guarded = [];

    public $timestamps = true;

    private const CACHE_KEY = 'settings.all';

    protected static function booted(): void
    {
        // Every page render reads settings, so they are cached as one array and
        // busted whenever the admin saves a change.
        static::saved(fn () => Cache::forget(self::CACHE_KEY));
        static::deleted(fn () => Cache::forget(self::CACHE_KEY));
    }

    /**
     * Deliberately not named all() — that would override Eloquent's static
     * all() with an incompatible return type and break anything (Filament
     * included) that expects a Collection back.
     *
     * @return array<string, string|null>
     */
    public static function map(): array
    {
        return Cache::rememberForever(
            self::CACHE_KEY,
            fn () => static::query()->pluck('value', 'key')->all(),
        );
    }

    public static function get(string $key, ?string $default = null): ?string
    {
        return static::map()[$key] ?? $default;
    }

    public static function put(string $key, ?string $value): void
    {
        static::updateOrCreate(['key' => $key], ['value' => $value]);
    }
}
