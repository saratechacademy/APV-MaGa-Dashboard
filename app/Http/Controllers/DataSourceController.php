<?php

namespace App\Http\Controllers;

use App\Models\Site;
use App\Services\LicorClient;
use App\Services\ThingsBoardClient;
use App\Services\ThingsBoardSync;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use RuntimeException;

/**
 * Admin → Data sources: what the ThingsBoard and LI-COR accounts actually
 * expose, and which site each source feeds. Linking a new source to a site
 * (or creating the site for it) is done here, so adding a site never needs a
 * code change or a deployment.
 */
class DataSourceController extends Controller
{
    private const LINKS = ['thingsboard' => 'thingsboard_prefix', 'licor' => 'licor_device_serial'];

    public function index()
    {
        $sites = Site::orderBy('name')->get();

        [$thingsboard, $thingsboardError] = $this->discover(fn () => $this->thingsboardSources());
        [$licor, $licorError]             = $this->discover(fn () => $this->licorSources());

        return view('admin.sources', compact('sites', 'thingsboard', 'thingsboardError', 'licor', 'licorError'));
    }

    public function link(Request $request)
    {
        $data = $request->validate([
            'source'  => ['required', Rule::in(array_keys(self::LINKS))],
            'key'     => 'required|string|max:50',
            'site_id' => 'nullable|exists:sites,id',
            'name'    => 'required_without:site_id|nullable|string|max:255',
            'country' => 'required_without:site_id|nullable|string|max:100',
        ]);

        $column = self::LINKS[$data['source']];

        if ($taken = Site::where($column, $data['key'])->first()) {
            return back()->with('error', "\"{$data['key']}\" already feeds the site \"{$taken->name}\".");
        }

        if (filled($data['site_id'] ?? null)) {
            $site = Site::findOrFail($data['site_id']);

            if (filled($site->{$column})) {
                return back()->with('error', "\"{$site->name}\" is already linked to \"{$site->{$column}}\". Unlink it first in Sites → Edit.");
            }
        } else {
            $site = new Site(['user_id' => Auth::id(), 'name' => $data['name'], 'country' => $data['country']]);
            $site->status = 'active';
        }

        $site->{$column} = $data['key'];
        $site->save();
        $site->alignOfflineThresholds($data['source']);

        return back()->with('success', "\"{$data['key']}\" now feeds the site \"{$site->name}\". Its parameters and data appear after the next sync (within 10 minutes).");
    }

    /** @return array{0: array, 1: ?string} the sources, or the reason they couldn't be listed */
    private function discover(callable $list): array
    {
        try {
            return [$list(), null];
        } catch (RuntimeException | ConnectionException | RequestException $e) {
            return [[], $e->getMessage()];
        }
    }

    /**
     * One source per device-name prefix that has at least one mapped device:
     * that is exactly the set of prefixes a site can be linked to.
     */
    private function thingsboardSources(): array
    {
        $client  = ThingsBoardClient::fromConfig();
        $devices = $client->devices();
        $sync    = new ThingsBoardSync($client);

        $prefixes = collect($devices)->pluck('name')
            ->map(fn ($name) => explode('-', (string) $name)[0])
            ->filter()->unique()->sort(SORT_NATURAL | SORT_FLAG_CASE);

        return $prefixes
            ->map(fn ($prefix) => ['key' => $prefix, 'label' => $prefix, 'detail' => count($sync->mappedDevices($prefix, $devices)) . ' mapped device(s)'])
            ->filter(fn ($source) => !str_starts_with($source['detail'], '0 '))
            ->values()->all();
    }

    private function licorSources(): array
    {
        return collect(LicorClient::fromConfig()->devices())
            ->map(fn ($station) => [
                'key'    => (string) $station['deviceSerialNumber'],
                'label'  => $station['deviceName'] ?? $station['deviceSerialNumber'],
                'detail' => count($station['sensors'] ?? []) . ' sensor(s)'
                    . (isset($station['lastConnectionTime']) ? ', last connection ' . \Illuminate\Support\Carbon::parse($station['lastConnectionTime'])->diffForHumans() : ''),
            ])
            ->sortBy('label')->values()->all();
    }
}
