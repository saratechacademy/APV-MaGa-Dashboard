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
 *
 * Single sensors are watched too, inside categories that are still
 * reporting: one dead soil probe or flow meter among working ones would
 * otherwise go unnoticed. They get a longer delay (config
 * alerts.sensor_threshold_multiplier), since isolated gaps of an hour or two
 * are routine on radio links.
 */
class SiteOfflineMonitor
{
    /**
     * @return array<int, array{site: string, down: string[], up: string[], sensorsDown: string[], sensorsUp: string[], recipients: int}>  what was announced
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

            $down = $up = $sensorsDown = $sensorsUp = [];

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

                // A whole category gone quiet is announced as such, not
                // sensor by sensor.
                if (!$offline) {
                    $this->checkSensors($category, $linkDown, $sensorsDown, $sensorsUp);
                }
            }

            if (!$down && !$up && !$sensorsDown && !$sensorsUp) {
                continue;
            }

            $recipients = $this->recipients($site);

            try {
                if ($recipients->isNotEmpty()) {
                    Mail::to($recipients)->send(new SiteStatusMail($site, $down, $up, $sensorsDown, $sensorsUp));
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
            foreach ($sensorsDown as $event) {
                $event['parameter']->forceFill(['offline_since' => $event['since']])->save();
            }
            foreach ($sensorsUp as $event) {
                $event['parameter']->forceFill(['offline_since' => null])->save();
            }

            $announced[] = [
                'site'       => $site->name,
                'down'       => array_map(fn ($e) => $e['category']->name, $down),
                'up'         => array_map(fn ($e) => $e['category']->name, $up),
                'sensorsDown' => array_map(fn ($e) => $e['parameter']->name, $sensorsDown),
                'sensorsUp'   => array_map(fn ($e) => $e['parameter']->name, $sensorsUp),
                'recipients' => $recipients->count(),
            ];
        }

        return $announced;
    }

    /**
     * The sensors of a reporting category that stopped, or came back, past
     * their own (longer) delay.
     */
    private function checkSensors(SiteCategory $category, bool $linkDown, array &$down, array &$up): void
    {
        $threshold = now()->subMinutes($category->offlineThresholdMinutes() * config('alerts.sensor_threshold_multiplier'));

        foreach ($category->activeParameters->filter(fn ($parameter) => $parameter->isSensor()) as $parameter) {
            $last = $parameter->latestReading?->read_at;
            if (!$last) {
                continue;   // never reported: nothing stopped
            }

            $silent = $last->lt($threshold);

            if ($silent && !$parameter->offline_since && !$linkDown) {
                $down[] = ['parameter' => $parameter, 'since' => $last];
            } elseif (!$silent && $parameter->offline_since) {
                $up[] = ['parameter' => $parameter, 'since' => $parameter->offline_since, 'back' => $last];
            }
        }
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
     * people assigned to it and its owner — among those who chose to receive
     * alerts (Admin → Users). Each address once.
     *
     * @return Collection<int, string>
     */
    private function recipients(Site $site): Collection
    {
        return User::where('status', 'active')
            ->where('receives_alerts', true)
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
