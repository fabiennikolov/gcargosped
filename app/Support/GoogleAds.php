<?php

namespace App\Support;

use App\Models\Setting;

/**
 * The Google Ads tag, as far as this site is concerned: an account to send the
 * hits to, and one conversion to report a lead against.
 *
 * Both values are typed by hand in the admin and both end up inside a <script>
 * — in the src of the tag library and in the argument of a gtag() call — so
 * neither is trusted as stored. Only the exact shape Google issues is
 * rendered; anything else is treated as no tag at all, because a page that
 * ships a malformed tag goes on looking tracked while sending nothing
 * anywhere.
 *
 * Nothing else has to be done to switch tracking on: the values live in the
 * settings table, which is read on every request and re-cached the moment the
 * admin saves. No deploy, no .env, no cache to clear.
 */
class GoogleAds
{
    /** As Google writes it: AW- and then the account's own characters. */
    private const ID_PATTERN = '/^AW-[0-9A-Za-z]+$/';

    /** The half of send_to behind the slash. */
    private const LABEL_PATTERN = '/^[0-9A-Za-z_-]+$/';

    /**
     * What the ID field accepts: the tag on its own, or the whole send_to —
     * "AW-123456789/AbC-D_efGh" is what is in the clipboard of anyone copying
     * out of the conversion's event snippet, and it carries both halves.
     */
    public const ID_INPUT_PATTERN = '/^AW-[0-9A-Za-z]+(?:\/[0-9A-Za-z_-]+)?$/';

    /** What the label field accepts: the label, with or without the ID in front. */
    public const LABEL_INPUT_PATTERN = '/^(?:AW-[0-9A-Za-z]+\/)?[0-9A-Za-z_-]+$/';

    /**
     * The ID the global tag loads with, or null when the site has not been
     * connected to an Ads account — in which case no tag is rendered at all.
     */
    public static function id(): ?string
    {
        return self::parse(Setting::get('google_ads_id'))[0];
    }

    /**
     * The conversion label on its own.
     *
     * Its own field first, and otherwise the half hiding behind the slash in
     * the ID field: one paste of send_to into either box is enough to
     * configure the whole thing.
     */
    public static function label(): ?string
    {
        return self::parse(Setting::get('google_ads_label'))[1]
            ?? self::parse(Setting::get('google_ads_id'))[1];
    }

    /**
     * What the conversion event names as its destination.
     *
     * Null unless both halves are configured: an event sent to an account
     * without naming a conversion is counted as nothing, and is worse than not
     * sending it — the Ads screen shows zero either way, while the site looks
     * instrumented.
     */
    public static function sendTo(): ?string
    {
        $id = self::id();
        $label = self::label();

        return $id !== null && $label !== null ? "{$id}/{$label}" : null;
    }

    /**
     * The two halves hiding in one pasted value, for the admin fields to tidy
     * themselves up with.
     *
     * Deliberately more forgiving than parse(): what Google puts on the screen
     * is a whole <script> block, and that is what gets copied. Only the
     * matched substrings are ever returned, so a stray paste can carry nothing
     * with it — the strict reading above is still what decides whether a
     * stored value is rendered.
     *
     * @return array{0: string|null, 1: string|null} the tag ID and the label
     */
    public static function extract(?string $raw): array
    {
        $value = trim((string) $raw);

        if ($value === '') {
            return [null, null];
        }

        $parsed = self::parse($value);

        if ($parsed !== [null, null]) {
            return $parsed;
        }

        // "…'send_to': 'AW-123456789/AbC-D_efGh'…" — both halves in one paste.
        if (preg_match('/(AW-[0-9A-Za-z]+)\/([0-9A-Za-z_-]+)/', $value, $match) === 1) {
            return [$match[1], $match[2]];
        }

        // The global tag block, which names the account and no conversion.
        if (preg_match('/AW-[0-9A-Za-z]+/', $value, $match) === 1) {
            return [$match[0], null];
        }

        return [null, null];
    }

    /**
     * Read one stored field as the pair Google issues. Strict: the value has
     * to be the ID, the label, or the two of them with a slash between, and
     * anything else is nothing at all.
     *
     * @return array{0: string|null, 1: string|null} the tag ID and the label
     */
    private static function parse(?string $raw): array
    {
        $value = trim((string) $raw);

        if ($value === '') {
            return [null, null];
        }

        [$first, $rest] = array_pad(explode('/', $value, 2), 2, null);

        // On its own, a value is whichever of the two halves it looks like.
        $isId = preg_match(self::ID_PATTERN, (string) $first) === 1;

        $label = $rest ?? ($isId ? null : $first);

        if ($label !== null && preg_match(self::LABEL_PATTERN, $label) !== 1) {
            return [$isId ? $first : null, null];
        }

        return [$isId ? $first : null, $label];
    }
}
