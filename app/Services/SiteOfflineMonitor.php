<?php

namespace App\Services;

use App\Mail\SiteStatusMail;
use App\Models\Site;
use App\Models\SiteCategory;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * Tells people when a site stops sending data, and when it starts again.
 *
 * Watched per category, not per site: a site whose soil probes died while its
 * weather station still reports is not "offline", yet someone has to go and
 * look at the probes. A category is offline when none of its sensors has sent
 * a reading within the category's own offline threshold — the same rule that
 * marks its cards "Stale" on the dashboard. One e-mail per site and per
 * check lists what just stopped and what just came back; nothing is sent
 * while the situation stays the same.
 */
class SiteOfflineMonitor
{
    /**
     * @return array<int, array{site: string, down: string[], up: string[], recipients: int}>  what was announced
     */
    public function check(): array
    {
        $announced = [];

        $sites = Site::where('status', 'active')
            ->with(['activeCategories.activeParameters.latestReading'])
            ->get();

        foreach ($sites as $site) {
            // When our own link to a data source is failing, missing data says
            // nothing about the site: the sync badge in the admin covers that.
            $linkDown = in_array($site->thingsboardSyncState(), ['failed', 'late'], true)
                || in_array($site->licorSyncState(), ['failed', 'late'], true);

            $down = $up = [];

            foreach ($site->activeCategories as $category) {
                $last = $this->lastSensorReading($category);
                if (!$last) {
                    continue;   // never reported: nothing stopped
                }

                $offline = $last->lt(now()->subMinutes($category->offlineThresholdMinutes()));

                if ($offline && !$category->offline_since && !$linkDown) {
                    $down[] = ['category' => $category, 'since' => $last];
                } elseif (!$offline && $category->offline_since) {
                    $up[] = ['category' => $category, 'since' => $category->offline_since, 'back' => $last];
                }
            }

            if (!$down && !$up) {
                continue;
            }

            $recipients = $this->recipients($site);

            try {
                if ($recipients->isNotEmpty()) {
                    Mail::to($recipients)->send(new SiteStatusMail($site, $down, $up));
                }
            } catch (\Throwable $e) {
                // State is left untouched, so the next check tries again.
                Log::error("Offline alert for {$site->name} could not be sent: {$e->getMessage()}");
                continue;
            }

            foreach ($down as $event) {
                $event['category']->forceFill(['offline_since' => $event['since']])->save();
            }
            foreach ($up as $event) {
                $event['category']->forceFill(['offline_since' => null])->save();
            }

            $announced[] = [
                'site'       => $site->name,
                'down'       => array_map(fn ($e) => $e['category']->name, $down),
                'up'         => array_map(fn ($e) => $e['category']->name, $up),
                'recipients' => $recipients->count(),
            ];
        }

        return $announced;
    }

    private function lastSensorReading(SiteCategory $category): ?Carbon
    {
        return $category->activeParameters
            ->filter(fn ($parameter) => $parameter->isSensor())
            ->map(fn ($parameter) => $parameter->latestReading?->read_at)
            ->filter()
            ->max();
    }

    /**
     * Active administrators, plus the active users attached to the site: the
     * people assigned to it and its owner. Each address once.
     *
     * @return Collection<int, string>
     */
    private function recipients(Site $site): Collection
    {
        return User::where('status', 'active')
            ->where(function ($query) use ($site) {
                $query->where('role', User::ROLE_ADMIN)
                    ->orWhere('id', $site->user_id)
                    ->orWhereIn('id', $site->users()->pluck('users.id'));
            })
            ->pluck('email')
            ->unique()
            ->values();
    }
}
