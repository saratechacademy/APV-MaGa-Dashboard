<?php

namespace App\Support;

/**
 * Lookups over config/countries.php.
 */
class Countries
{
    /**
     * ISO alpha-3 code of a country name (or one of its aliases). A name
     * typed before the list existed and not in it falls back to its first
     * three letters, as the UI always did.
     */
    public static function code(?string $name): string
    {
        foreach (config('countries') as $country => $info) {
            if ($country === $name || in_array($name, $info['aliases'] ?? [], true)) {
                return $info['code'];
            }
        }

        return strtoupper(mb_substr((string) $name, 0, 3));
    }
}
