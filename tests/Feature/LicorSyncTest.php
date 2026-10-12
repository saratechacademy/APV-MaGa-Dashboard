<?php

namespace Tests\Feature;

use App\Models\SensorReading;
use App\Models\Site;
use App\Models\SiteParameter;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

/**
 * licor:sync runs unattended every ten minutes and stores 10-minute buckets
 * of 1-minute data: a bucketing or resume bug shows up as wrong averages,
 * gaps or doubled points on the weather charts with nobody watching.
 */
class LicorSyncTest extends TestCase
{
    use RefreshDatabase;

    private array $records = [];

    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow('2026-10-12 12:00:00');
        config(['licor.url' => 'http://licor.test/v2', 'licor.token' => 'secret']);
    }

    private function ms(string $time): int
    {
        return Carbon::parse($time)->getTimestampMs();
    }

    /** One record per minute from $from (inclusive) to $to (exclusive). */
    private function minutes(string $from, string $to, callable $value): array
    {
        $rows = [];
        for ($t = $this->ms($from), $i = 0; $t < $this->ms($to); $t += 60000, $i++) {
            $rows[] = [$t, $value($i)];
        }

        return $rows;
    }

    /** @param array<string, array{0: string, 1: array}> $sensors serial => [measurementType, records] */
    private function fakeLicor(array $sensors, string $device = '22490036'): void
    {
        $this->records = $sensors;

        Http::fake([
            'licor.test/v2/devices*' => Http::response(['devices' => [[
                'deviceSerialNumber' => $device,
                'sensors' => collect($sensors)->map(fn ($s, $serial) => ['sensorSerialNumber' => $serial, 'measurementType' => $s[0]])->values()->all(),
            ]]]),
            'licor.test/v2/data*' => function (Request $request) {
                $sensors = [];
                foreach ($this->records as $serial => [$type, $records]) {
                    $inWindow = array_values(array_filter($records, fn ($r) => $r[0] >= $request['startTime'] && $r[0] <= $request['endTime']));
                    $sensors[] = ['sensorSerialNumber' => $serial, 'data' => [['measurementType' => $type, 'records' => $inWindow]]];
                }

                return Http::response(['moreResults' => false, 'sensors' => $sensors]);
            },
        ]);
    }

    private function makeSite(): Site
    {
        $site = Site::factory()->create(['status' => 'active']);
        $site->forceFill(['licor_device_serial' => '22490036'])->save();

        return $site;
    }

    private function series(Site $site, string $slug): array
    {
        $param = SiteParameter::where('slug', $slug)->whereHas('category', fn ($q) => $q->where('site_id', $site->id))->firstOrFail();

        return SensorReading::where('site_parameter_id', $param->id)->orderBy('read_at')->get()
            ->mapWithKeys(fn ($r) => [$r->read_at->format('H:i') => round($r->value, 2)])->all();
    }

    public function test_stores_complete_10_minute_buckets_aggregated_per_measurement(): void
    {
        $site = $this->makeSite();

        $this->fakeLicor([
            '22034652-1' => ['Temperature', $this->minutes('11:30', '11:55', fn ($i) => 30 + $i)],
            '22250333-1' => ['Rain', $this->minutes('11:30', '11:55', fn () => 0.2)],
            '21930662-2' => ['Gust Speed', $this->minutes('11:30', '11:55', fn ($i) => $i === 3 ? 9 : 2)],
            '21930662-3' => ['Wind Direction', $this->minutes('11:30', '11:50', fn ($i) => $i % 2 ? 350 : 10)],
            '22261664-1' => ['PAR', $this->minutes('11:30', '11:55', fn () => 1500)],
            '99999999-1' => ['Battery', $this->minutes('11:30', '11:55', fn () => 3.6)],
        ]);

        $this->artisan('licor:sync')
            ->expectsOutputToContain('5 sensor(s), 10 reading(s) stored, 0 rejected, 5 parameter(s) created.')
            ->assertSuccessful();

        // 11:50 bucket is still open (last record 11:54): not stored yet.
        $this->assertSame(['11:30' => 34.5, '11:40' => 44.5], $this->series($site, 'licor_22034652_1'));
        $this->assertSame(['11:30' => 2.0, '11:40' => 2.0], $this->series($site, 'licor_22250333_1'));
        $this->assertSame(['11:30' => 9.0, '11:40' => 2.0], $this->series($site, 'licor_21930662_2'));
        $this->assertEqualsWithDelta(0, $this->series($site, 'licor_21930662_3')['11:30'] % 360, 0.01);

        $par = SiteParameter::where('slug', 'licor_22261664_1')->first();
        $this->assertSame('Under PV – PAR', $par->name);
        $this->assertSame('solar', $par->category->slug);

        $temp = SiteParameter::where('slug', 'licor_22034652_1')->first();
        $this->assertSame('Air temperature – 22034652', $temp->name);
        $this->assertSame('weather', $temp->category->slug);
        $this->assertSame('Weather stations', $temp->group->name);
        $this->assertSame(['Air temperature (°C)', 'Wind (m/s)', 'Wind direction (°)', 'Rain (mm)'],
            $temp->category->charts()->pluck('title')->all());

        $this->assertSame('ok', $site->refresh()->licorSyncState());
    }

    public function test_next_run_completes_the_open_bucket_without_duplicating(): void
    {
        $site = $this->makeSite();

        $this->fakeLicor(['22034652-1' => ['Temperature', $this->minutes('11:30', '11:55', fn () => 20)]]);
        $this->artisan('licor:sync')->assertSuccessful();

        $this->fakeLicor(['22034652-1' => ['Temperature', $this->minutes('11:30', '12:01', fn () => 20)]]);
        $this->artisan('licor:sync')
            ->expectsOutputToContain('1 reading(s) stored')
            ->assertSuccessful();

        $this->assertSame(['11:30' => 20.0, '11:40' => 20.0, '11:50' => 20.0], $this->series($site, 'licor_22034652_1'));

        // The second run asked again from the first unstored bucket only.
        $starts = Http::recorded(fn (Request $r) => str_contains($r->url(), '/data'))
            ->map(fn ($pair) => (int) $pair[0]['startTime'])->values()->all();
        $this->assertSame($this->ms('11:50'), end($starts));
    }

    public function test_a_failing_station_is_reported_without_blocking_the_other_sites(): void
    {
        Log::spy();
        $site  = $this->makeSite();
        $other = Site::factory()->create(['status' => 'active']);
        $other->forceFill(['licor_device_serial' => '22490037'])->save();

        Http::fake([
            'licor.test/v2/devices*' => Http::response(['devices' => [
                ['deviceSerialNumber' => '22490036', 'sensors' => [['sensorSerialNumber' => '1-1', 'measurementType' => 'RH']]],
                ['deviceSerialNumber' => '22490037', 'sensors' => [['sensorSerialNumber' => '2-1', 'measurementType' => 'Pressure']]],
            ]]),
            'licor.test/v2/data*deviceSerialNumber=22490036*' => Http::response('down', 500),
            'licor.test/v2/data*' => Http::response(['moreResults' => false, 'sensors' => [[
                'sensorSerialNumber' => '2-1',
                'data' => [['records' => $this->minutes('11:30', '11:45', fn () => 1012)]],
            ]]]),
        ]);

        $this->artisan('licor:sync')->expectsOutputToContain('1 reading(s) stored')->assertFailed();

        $this->assertSame(['11:30' => 1012.0], $this->series($other, 'licor_2_1'));
        $this->assertSame('ok', $other->refresh()->licorSyncState());
        $this->assertSame('failed', $site->refresh()->licorSyncState());
        $this->assertStringStartsWith('station 22490036: ', $site->licor_sync_error);
    }

    public function test_paged_responses_are_followed_without_losing_or_doubling_records(): void
    {
        // One day back = a single day-window, so the two pages below are its only requests.
        config(['licor.lookback_days' => 1]);
        $site = $this->makeSite();
        $temp = $this->minutes('11:30', '11:50', fn () => 25);
        $rain = $this->minutes('11:30', '11:50', fn () => 0.1);

        $page = fn (array $t, array $r, bool $more) => ['moreResults' => $more, 'sensors' => [
            ['sensorSerialNumber' => 'T-1', 'data' => [['records' => $t]]],
            ['sensorSerialNumber' => 'R-1', 'data' => [['records' => $r]]],
        ]];

        Http::fake([
            'licor.test/v2/devices*' => Http::response(['devices' => [['deviceSerialNumber' => '22490036', 'sensors' => [
                ['sensorSerialNumber' => 'T-1', 'measurementType' => 'Temperature'],
                ['sensorSerialNumber' => 'R-1', 'measurementType' => 'Rain'],
            ]]]]),
            // First page cut mid-station: temperature complete, rain only up to 11:34.
            'licor.test/v2/data*' => Http::sequence()
                ->push($page($temp, array_slice($rain, 0, 5), true))
                ->push($page(array_slice($temp, 5), array_slice($rain, 5), false)),
        ]);

        $this->artisan('licor:sync')->assertSuccessful();

        $this->assertSame(['11:30' => 25.0, '11:40' => 25.0], $this->series($site, 'licor_T_1'));
        $this->assertSame(['11:30' => 1.0, '11:40' => 1.0], $this->series($site, 'licor_R_1'));
    }

    public function test_setup_links_existing_sites_creates_fass_and_adds_agriculture_parameters(): void
    {
        User::factory()->create(['role' => 'admin', 'status' => 'active']);
        $utg = Site::factory()->create(['name' => 'University of The Gambia', 'status' => 'active']);
        $utg->forceFill(['thingsboard_prefix' => 'UTG'])->save();

        Http::fake([
            'licor.test/v2/devices*' => Http::response(['devices' => [
                ['deviceSerialNumber' => '22490036', 'sensors' => []],
                ['deviceSerialNumber' => '22490037', 'sensors' => []],
            ]]),
        ]);

        $this->artisan('licor:setup')
            ->expectsOutputToContain('22490036: linking existing site "University of The Gambia"')
            ->expectsOutputToContain('22490035: station not found on LI-COR, skipped.')
            ->expectsOutputToContain('22490037: creating site "Fass Community Garden"')
            ->assertSuccessful();

        $this->assertSame('22490036', $utg->refresh()->licor_device_serial);
        $fass = Site::where('licor_device_serial', '22490037')->firstOrFail();
        $this->assertSame('Gambia', $fass->country);

        $agri = $fass->categories()->where('slug', 'agriculture')->firstOrFail();
        $this->assertSame(9, $agri->parameters()->where('input_type', 'manual')->count());
        $this->assertSame('mg/g FM', $agri->parameters()->where('slug', 'chlorophyll_content')->value('unit'));

        $this->artisan('licor:setup')->assertSuccessful();
        $this->assertSame(2, Site::count());
        $this->assertSame(9, $agri->parameters()->count());
    }
}
