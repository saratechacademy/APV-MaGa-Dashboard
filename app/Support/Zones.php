<?php

namespace App\Support;

/**
 * Where on a site a parameter is measured. Agrivoltaic research compares the
 * same quantity between the open reference plot and the plot under the
 * panels, so every parameter that has a location carries one of these.
 */
class Zones
{
    public const REFERENCE   = 'reference';
    public const APV         = 'apv';
    public const UNDER_PV    = 'under_pv';
    public const BETWEEN_PV  = 'between_pv';
    public const MODULE_EDGE = 'module_edge';

    /** In display order: the reference comes first, as the baseline. */
    public const LABELS = [
        self::REFERENCE   => 'Reference',
        self::APV         => 'APV field',
        self::UNDER_PV    => 'Under PV',
        self::BETWEEN_PV  => 'Between PV',
        self::MODULE_EDGE => 'Module edge',
    ];

    public static function label(?string $zone): string
    {
        return self::LABELS[$zone] ?? 'Unspecified';
    }

    /**
     * The zone of a parameter from what the data sources already record:
     * the location in a PAR metric (par_under_pv), a LI-COR location used
     * as group name ("Under PV"), or a ThingsBoard field group ("APV field").
     */
    public static function infer(?string $metric, ?string $groupName): ?string
    {
        if ($metric && str_starts_with($metric, 'par_') && isset(self::LABELS[substr($metric, 4)])) {
            return substr($metric, 4);
        }

        return self::fromName($groupName);
    }

    /** "Under PV", "Between rows", "Reference field", "APV field"… → zone. */
    public static function fromName(?string $name): ?string
    {
        $name = strtolower(trim((string) $name));

        return match (true) {
            $name === '' => null,
            str_starts_with($name, 'reference') => self::REFERENCE,
            str_starts_with($name, 'under') => self::UNDER_PV,
            str_starts_with($name, 'between') => self::BETWEEN_PV,
            str_starts_with($name, 'module edge'), str_starts_with($name, 'edge') => self::MODULE_EDGE,
            str_starts_with($name, 'apv') => self::APV,
            default => null,
        };
    }
}
