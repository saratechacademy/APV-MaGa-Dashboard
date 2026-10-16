<?php

namespace Tests\Feature;

use App\Models\ManualReading;
use App\Models\SensorReading;
use App\Models\Site;
use App\Models\SiteCategory;
use App\Models\SiteParameter;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

/**
 * Raw data on phones: each parameter lists its own newest readings, so a
 * sensor that reports rarely is not lost among empty table cells.
 */
class RawSensorsViewTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private Site $site;

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow('2026-10-13 12:00:00');

        $this->admin = User::factory()->create(['role' => User::ROLE_ADMIN, 'status' => 'active']);
        $this->site  = Site::factory()->create(['status' => 'active']);
    }

    private function parameter(string $category, array $attrs): SiteParameter
    {
        $cat = SiteCategory::firstOrCreate(
            ['site_id' => $this->site->id, 'slug' => $category],
            ['name' => ucfirst($category), 'is_active' => true, 'sort_order' => 0],
        );

        return SiteParameter::factory()->create(['site_category_id' => $cat->id, 'is_active' => true] + $attrs);
    }

    private function readings(SiteParameter $param, int $count, int $everyMinutes, callable $value): void
    {
        for ($i = 0; $i < $count; $i++) {
            SensorReading::factory()->create([
                'site_id' => $this->site->id, 'site_parameter_id' => $param->id, 'value' => $value($i),
                'read_at' => Carbon::parse('2026-10-13 11:50:00')->subMinutes($everyMinutes * $i),
            ]);
        }
    }

    public function test_each_parameter_shows_its_own_latest_readings_and_count(): void
    {
        $temp  = $this->parameter('weather', ['name' => 'Air temperature', 'unit' => '°C', 'slug' => 'temp']);
        $rare  = $this->parameter('weather', ['name' => 'Rain gauge', 'unit' => 'mm', 'slug' => 'rain']);
        $valve = $this->parameter('irrigation', ['name' => 'Valve 1', 'unit' => null, 'slug' => 'valve', 'data_type' => 'switch']);
        $quiet = $this->parameter('irrigation', ['name' => 'Flow meter', 'unit' => 'L', 'slug' => 'flow']);

        $this->readings($temp, 30, 10, fn ($i) => 30 - $i / 10);          // every 10 min
        $this->readings($rare, 2, 300, fn ($i) => 1.5);                    // twice in 24 h
        $this->readings($valve, 1, 10, fn () => 1);

        $this->actingAs($this->admin)
            ->get(route('dashboard.raw-sensors', $this->site->slug))
            ->assertOk()
            ->assertSeeInOrder(['Air temperature', '30', '30 readings', 'last 13 Oct 11:50 UTC'])
            ->assertSeeInOrder(['Rain gauge', '1.5', '2 readings'])
            ->assertSee('ON')
            ->assertSeeInOrder(['Flow meter', 'No reading in this period'])
            ->assertSee('Show older readings');   // temperature has more than 10
    }

    public function test_show_more_returns_the_next_older_readings_of_one_parameter(): void
    {
        $temp = $this->parameter('weather', ['name' => 'Air temperature', 'unit' => '°C', 'slug' => 'temp']);
        $this->readings($temp, 25, 10, fn ($i) => $i);   // values 0 (newest) … 24 (oldest)

        $html = $this->actingAs($this->admin)
            ->get(route('dashboard.raw-sensors', $this->site->slug) . '?parameter=' . $temp->id . '&before=' . urlencode(Carbon::parse('2026-10-13 10:20:00')->toIso8601String()))
            ->assertOk()
            ->getContent();

        // Strictly older than 10:20 (value 9): values 10 … 24, the 15 that remain.
        $this->assertSame(15, substr_count($html, 'class="rs-row"'));
        $this->assertStringContainsString('13 Oct 2026 10:10', $html);
        $this->assertStringNotContainsString('13 Oct 2026 10:20', $html);
        $this->assertStringNotContainsString('Show older readings', $html);
    }

    public function test_manual_entries_are_listed_too(): void
    {
        $height = $this->parameter('agriculture', ['name' => 'Plant height', 'unit' => 'cm', 'slug' => 'height', 'input_type' => 'manual']);
        ManualReading::create([
            'site_id' => $this->site->id, 'site_parameter_id' => $height->id, 'user_id' => $this->admin->id,
            'value' => '42.5', 'reading_date' => '2026-10-13 08:00:00',
        ]);

        $this->actingAs($this->admin)
            ->get(route('dashboard.raw-sensors', $this->site->slug))
            ->assertOk()
            ->assertSeeInOrder(['Plant height', '42.5', '1 reading', 'manual entry']);
    }

    public function test_only_people_with_access_to_the_site_can_read_it(): void
    {
        $outsider = User::factory()->create(['role' => User::ROLE_OBSERVER, 'status' => 'active']);
        $temp = $this->parameter('weather', ['name' => 'Air temperature', 'slug' => 'temp']);

        $this->actingAs($outsider)->get(route('dashboard.raw-sensors', $this->site->slug))->assertForbidden();

        // A parameter of another site cannot be read through this one.
        $other = Site::factory()->create(['status' => 'active']);
        $this->site->users()->attach($outsider->id, ['role' => User::ROLE_OBSERVER]);
        $foreign = SiteParameter::factory()->create([
            'site_category_id' => SiteCategory::factory()->create(['site_id' => $other->id])->id,
        ]);
        $this->actingAs($outsider)
            ->get(route('dashboard.raw-sensors', $this->site->slug) . '?parameter=' . $foreign->id)
            ->assertNotFound();
    }
}
