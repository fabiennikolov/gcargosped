<?php

namespace App\Support;

use Illuminate\Support\Str;

class Slug
{
    /**
     * Str::slug() strips Cyrillic outright, which would collapse every
     * Bulgarian title to an empty slug, so text is romanised first.
     */
    public static function make(string $text, string $fallback = ''): string
    {
        $map = [
            'а' => 'a', 'б' => 'b', 'в' => 'v', 'г' => 'g', 'д' => 'd', 'е' => 'e',
            'ж' => 'zh', 'з' => 'z', 'и' => 'i', 'й' => 'y', 'к' => 'k', 'л' => 'l',
            'м' => 'm', 'н' => 'n', 'о' => 'o', 'п' => 'p', 'р' => 'r', 'с' => 's',
            'т' => 't', 'у' => 'u', 'ф' => 'f', 'х' => 'h', 'ц' => 'ts', 'ч' => 'ch',
            'ш' => 'sh', 'щ' => 'sht', 'ъ' => 'a', 'ь' => 'y', 'ю' => 'yu', 'я' => 'ya',
        ];

        return Str::slug(strtr(mb_strtolower($text), $map)) ?: $fallback;
    }
}
