<?php

namespace App\Support;

use App\Models\Setting;

/**
 * Every tracking and verification code the site carries apart from Google Ads
 * (which has its own class, because it is two fields that complete each
 * other): Google Analytics 4, Google Tag Manager, one or more Meta pixels, the
 * LinkedIn Insight Tag, and the two meta tags that prove the domain is ours.
 *
 * All of them are typed — more often pasted — by hand in the admin, and all of
 * them are rendered into the <head>, most of them inside a <script>. So the
 * same rule as for Google Ads: nothing is trusted as stored, only the exact
 * shape each vendor issues is rendered, and anything else is no tag at all.
 *
 * extract() is the forgiving half, for the admin fields: what the vendors put
 * on screen is a block of markup, and that is what gets copied. It only ever
 * returns the matched substrings, so a stray paste carries nothing with it.
 */
class Marketing
{
    /** Google Analytics 4 measurement ID. */
    public const GA4_PATTERN = '/^G-[0-9A-Z]{4,20}$/';

    /** Google Tag Manager container ID. */
    public const GTM_PATTERN = '/^GTM-[0-9A-Z]{4,20}$/';

    /** A Meta (Facebook / Instagram) pixel — a dataset ID, digits only. */
    public const META_PIXEL_PATTERN = '/^\d{8,20}$/';

    /** LinkedIn Insight Tag partner ID. */
    public const LINKEDIN_PARTNER_PATTERN = '/^\d{3,12}$/';

    /** LinkedIn conversion ID, sent when an inquiry goes out. */
    public const LINKEDIN_CONVERSION_PATTERN = '/^\d{3,15}$/';

    /** The content of <meta name="google-site-verification">. */
    public const GOOGLE_VERIFICATION_PATTERN = '/^[0-9A-Za-z_-]{10,100}$/';

    /** The content of <meta name="facebook-domain-verification">. */
    public const FACEBOOK_VERIFICATION_PATTERN = '/^[0-9A-Za-z]{10,64}$/';

    /** How many pixels the site will fire at once. More is a mistake, not a strategy. */
    public const MAX_META_PIXELS = 5;

    public static function ga4Id(): ?string
    {
        return self::strict(Setting::get('ga4_id'), self::GA4_PATTERN);
    }

    public static function gtmId(): ?string
    {
        return self::strict(Setting::get('gtm_id'), self::GTM_PATTERN);
    }

    /**
     * The pixels to initialise, in the order they were entered, duplicates and
     * anything malformed dropped. Empty until the admin fills the field in.
     *
     * @return array<int, string>
     */
    public static function metaPixelIds(): array
    {
        $ids = array_filter(
            self::splitList(Setting::get('meta_pixel_ids')),
            fn (string $id) => preg_match(self::META_PIXEL_PATTERN, $id) === 1,
        );

        return array_slice(array_values(array_unique($ids)), 0, self::MAX_META_PIXELS);
    }

    public static function linkedinPartnerId(): ?string
    {
        return self::strict(Setting::get('linkedin_partner_id'), self::LINKEDIN_PARTNER_PATTERN);
    }

    /**
     * Null unless the Insight Tag itself is configured: a conversion sent
     * through a tag that never loaded goes nowhere.
     */
    public static function linkedinLeadId(): ?string
    {
        if (self::linkedinPartnerId() === null) {
            return null;
        }

        return self::strict(Setting::get('linkedin_lead_id'), self::LINKEDIN_CONVERSION_PATTERN);
    }

    public static function googleVerification(): ?string
    {
        return self::strict(Setting::get('google_site_verification'), self::GOOGLE_VERIFICATION_PATTERN);
    }

    public static function facebookVerification(): ?string
    {
        return self::strict(Setting::get('facebook_domain_verification'), self::FACEBOOK_VERIFICATION_PATTERN);
    }

    /*
     * The forgiving readings, one per field, for the admin to tidy a paste
     * up with. Each returns the bare value or null when nothing recognisable
     * was in it — in which case the field keeps what was typed and the
     * validation says what is wrong with it.
     */

    public static function extractGa4(?string $raw): ?string
    {
        return self::firstMatch($raw, '/\bG-[0-9A-Z]{4,20}\b/');
    }

    public static function extractGtm(?string $raw): ?string
    {
        return self::firstMatch($raw, '/\bGTM-[0-9A-Z]{4,20}\b/');
    }

    /**
     * Every pixel ID in the paste — the base code names its pixel twice, in
     * fbq('init') and in the <noscript> image, and a list typed by hand may
     * separate them with anything.
     *
     * @return array<int, string>
     */
    public static function extractMetaPixels(?string $raw): array
    {
        preg_match_all('/(?<!\d)\d{8,20}(?!\d)/', (string) $raw, $matches);

        return array_values(array_unique($matches[0]));
    }

    public static function extractLinkedinPartner(?string $raw): ?string
    {
        return self::firstMatch($raw, '/_linkedin_partner_id\s*=\s*["\']?(\d{3,12})/', 1)
            ?? self::firstMatch($raw, '/[?&]pid=(\d{3,12})/', 1)
            ?? self::firstMatch($raw, '/^\s*(\d{3,12})\s*$/', 1);
    }

    public static function extractLinkedinConversion(?string $raw): ?string
    {
        return self::firstMatch($raw, '/conversion_?id["\']?\s*[:=]\s*["\']?(\d{3,15})/i', 1)
            ?? self::firstMatch($raw, '/^\s*(\d{3,15})\s*$/', 1);
    }

    /** The meta tag, the DNS TXT record form, or the token on its own. */
    public static function extractGoogleVerification(?string $raw): ?string
    {
        return self::extractVerification($raw, 'google-site-verification', '[0-9A-Za-z_-]{10,100}');
    }

    public static function extractFacebookVerification(?string $raw): ?string
    {
        return self::extractVerification($raw, 'facebook-domain-verification', '[0-9A-Za-z]{10,64}');
    }

    private static function extractVerification(?string $raw, string $name, string $token): ?string
    {
        return self::firstMatch($raw, '/content\s*=\s*["\']('.$token.')["\']/', 1)
            ?? self::firstMatch($raw, '/'.$name.'\s*=\s*('.$token.')/', 1)
            ?? self::firstMatch($raw, '/^\s*('.$token.')\s*$/', 1);
    }

    /**
     * Split a stored list on anything that is not part of an ID: newlines are
     * what the admin saves, but a hand-edited value may use commas.
     *
     * @return array<int, string>
     */
    public static function splitList(?string $raw): array
    {
        return array_values(array_filter(
            preg_split('/[\s,;]+/', trim((string) $raw)) ?: [],
            fn (string $item) => $item !== '',
        ));
    }

    private static function strict(?string $raw, string $pattern): ?string
    {
        $value = trim((string) $raw);

        return preg_match($pattern, $value) === 1 ? $value : null;
    }

    private static function firstMatch(?string $raw, string $pattern, int $group = 0): ?string
    {
        return preg_match($pattern, (string) $raw, $match) === 1 ? $match[$group] : null;
    }
}
