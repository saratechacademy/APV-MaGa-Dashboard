<?php

namespace App\Support;

use App\Models\Site;
use App\Models\User;
use RuntimeException;

/**
 * The site presets file (database/presets/sites.json, or the one passed with
 * --presets) and the one rule both setup commands share for turning a preset
 * into a site: reuse what exists, never duplicate, never overwrite what an
 * admin already set.
 */
class SitePresets
{
    /** @return array<int, array<string, mixed>> */
    public static function load(?string $path = null): array
    {
        $path ??= database_path('presets/sites.json');

        if (!is_file($path)) {
            throw new RuntimeException("Presets file not found: {$path}");
        }

        $data = json_decode(file_get_contents($path), true);

        if (!is_array($data) || !isset($data['sites']) || !is_array($data['sites'])) {
            throw new RuntimeException("Presets file is not valid JSON with a \"sites\" list: {$path}");
        }

        return $data['sites'];
    }

    /**
     * Finds the site a preset describes, or prepares a new one.
     *
     * @param  string  $linkColumn  the column this integration links by (thingsboard_prefix / licor_device_serial)
     * @return array{0: Site|string|null, 1: string}  the site and what happens to it
     *                                                ('already linked to' / 'linking existing' / 'creating'),
     *                                                or 'ambiguous' with the candidates' names, or 'no-owner'
     */
    public static function resolve(array $preset, string $linkColumn, ?string $ownerEmail): array
    {
        if ($site = Site::where($linkColumn, $preset[$linkColumn])->first()) {
            return [$site, 'already linked to'];
        }

        // The same site, already set up through the other integration.
        foreach (['thingsboard_prefix', 'licor_device_serial'] as $other) {
            if ($other !== $linkColumn && filled($preset[$other] ?? null)
                && $site = Site::whereNull($linkColumn)->where($other, $preset[$other])->first()) {
                return [$site, 'linking existing'];
            }
        }

        // An installation that predates the integrations already has its sites,
        // under names like "UTG Agrivoltaic Site - GAM".
        $match = $preset['match'] ?? $preset['name'];
        $candidates = Site::whereNull($linkColumn)
            ->where(fn ($q) => $q->where('name', 'like', "%{$match}%")->orWhere('name', $preset['name']))
            ->get();

        if ($candidates->count() > 1) {
            return [$candidates->pluck('name')->implode(', '), 'ambiguous'];
        }
        if ($candidates->count() === 1) {
            return [$candidates->first(), 'linking existing'];
        }

        $owner = $ownerEmail
            ? User::where('email', $ownerEmail)->first()
            : User::where('role', User::ROLE_ADMIN)->where('status', 'active')->orderBy('id')->first();

        if (!$owner) {
            return [null, 'no-owner'];
        }

        $site = new Site(['user_id' => $owner->id, 'name' => $preset['name'], 'country' => $preset['country'] ?? '']);
        $site->status = 'active';

        return [$site, 'creating'];
    }

    /**
     * Copies the preset's facts onto the site, only where the site has none
     * yet: what an admin entered always wins over the file.
     */
    public static function fill(Site $site, array $preset): void
    {
        $hadTank = filled($site->tank_radius_cm);

        foreach (['latitude', 'longitude', 'thingsboard_prefix', 'licor_device_serial', 'tank_radius_cm', 'licor_locations'] as $field) {
            if (blank($site->{$field}) && filled($preset[$field] ?? null)) {
                $site->{$field} = $preset[$field];
            }
        }

        // The number of tanks only means something together with their radius.
        if (!$hadTank && isset($preset['tank_count'])) {
            $site->tank_count = $preset['tank_count'];
        }
    }
}
