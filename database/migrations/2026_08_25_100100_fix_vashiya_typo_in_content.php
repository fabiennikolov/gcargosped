<?php

use App\Models\Setting;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * "Вашия товар е" → "Вашият товар е" in content that is already in the
 * database — the slogan, wherever it was typed.
 *
 * The wording lives in the settings table and in the service and post bodies,
 * so fixing the seed file alone would leave the running site saying it. The
 * phrase is matched down to the "е" rather than as a bare "Вашия": a subject
 * standing before the copula always takes the full article, while the short
 * form after a preposition ("за вашия товар") is correct as it is, and a
 * blanket replace would introduce the mistake it is meant to remove. The hero
 * title carries HTML between "е" and "наша грижа", so the slogan is not one
 * contiguous string everywhere it appears.
 */
return new class extends Migration
{
    private const WRONG = 'Вашия товар е';

    private const RIGHT = 'Вашият товар е';

    /** @var array<string, array<int, string>> */
    private const TARGETS = [
        'settings' => ['value'],
        'services' => ['title', 'subtitle', 'body', 'seo_title', 'seo_description'],
        'posts' => ['title', 'excerpt', 'body', 'seo_title', 'seo_description'],
    ];

    public function up(): void
    {
        $this->replace(self::WRONG, self::RIGHT);
    }

    public function down(): void
    {
        $this->replace(self::RIGHT, self::WRONG);
    }

    private function replace(string $from, string $to): void
    {
        foreach (self::TARGETS as $table => $columns) {
            foreach ($columns as $column) {
                DB::table($table)
                    ->where($column, 'like', '%'.$from.'%')
                    ->update([$column => DB::raw(
                        // REPLACE() is in SQLite, MySQL and Postgres alike, and
                        // does the work in one statement instead of reading
                        // every row into PHP.
                        sprintf('REPLACE(%s, %s, %s)', $column, $this->quote($from), $this->quote($to)),
                    )]);
            }
        }

        // The settings table is read through a cache that only Eloquent writes
        // bust, and the UPDATE above is not one.
        Setting::forgetCache();
    }

    private function quote(string $value): string
    {
        return "'".str_replace("'", "''", $value)."'";
    }
};
