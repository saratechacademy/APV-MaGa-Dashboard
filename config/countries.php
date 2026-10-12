<?php

// The countries offered in every country selector (sites, users, registration)
// and the ISO 3166-1 alpha-3 code shown as a site's country chip. One list,
// used through <x-country-options> and App\Support\Countries.
//
// 'aliases' are spellings older forms stored for the same country; they keep
// resolving to the right code.
return [
    'Benin'                    => ['code' => 'BEN', 'flag' => '🇧🇯'],
    'Burkina Faso'             => ['code' => 'BFA', 'flag' => '🇧🇫'],
    'Cameroon'                 => ['code' => 'CMR', 'flag' => '🇨🇲'],
    'Cape Verde'               => ['code' => 'CPV', 'flag' => '🇨🇻'],
    'Central African Republic' => ['code' => 'CAF', 'flag' => '🇨🇫'],
    'Chad'                     => ['code' => 'TCD', 'flag' => '🇹🇩'],
    "Côte d'Ivoire"            => ['code' => 'CIV', 'flag' => '🇨🇮', 'aliases' => ['Ivory Coast', "Cote d'Ivoire"]],
    'Gambia'                   => ['code' => 'GMB', 'flag' => '🇬🇲'],
    'Ghana'                    => ['code' => 'GHA', 'flag' => '🇬🇭'],
    'Guinea'                   => ['code' => 'GIN', 'flag' => '🇬🇳'],
    'Guinea-Bissau'            => ['code' => 'GNB', 'flag' => '🇬🇼'],
    'Liberia'                  => ['code' => 'LBR', 'flag' => '🇱🇷'],
    'Mali'                     => ['code' => 'MLI', 'flag' => '🇲🇱'],
    'Mauritania'               => ['code' => 'MRT', 'flag' => '🇲🇷'],
    'Niger'                    => ['code' => 'NER', 'flag' => '🇳🇪'],
    'Nigeria'                  => ['code' => 'NGA', 'flag' => '🇳🇬'],
    'Senegal'                  => ['code' => 'SEN', 'flag' => '🇸🇳'],
    'Sierra Leone'             => ['code' => 'SLE', 'flag' => '🇸🇱'],
    'Togo'                     => ['code' => 'TGO', 'flag' => '🇹🇬'],

    'Algeria'                  => ['code' => 'DZA', 'flag' => '🇩🇿'],
    'Egypt'                    => ['code' => 'EGY', 'flag' => '🇪🇬'],
    'Ethiopia'                 => ['code' => 'ETH', 'flag' => '🇪🇹'],
    'Kenya'                    => ['code' => 'KEN', 'flag' => '🇰🇪'],
    'Morocco'                  => ['code' => 'MAR', 'flag' => '🇲🇦'],
    'South Africa'             => ['code' => 'ZAF', 'flag' => '🇿🇦'],
    'Tanzania'                 => ['code' => 'TZA', 'flag' => '🇹🇿'],
    'Tunisia'                  => ['code' => 'TUN', 'flag' => '🇹🇳'],
];
