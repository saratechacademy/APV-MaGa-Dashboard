<?php

namespace Tests\Feature;

use App\Models\ManualReading;
use App\Models\SensorReading;
use App\Models\Site;
use App\Models\SiteCategory;
use App\Models\SiteParameter;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * SiteAuthorizationTest already covers who is allowed to export; this covers
 * what actually ends up in the file — the CSV endpoints stream their content
 * via response()->stream(), which TestResponse::getContent() doesn't capture,
 * so every assertion here goes through the manual sendContent() capture.
 */
class ExportContentTest extends TestCase
{
    use RefreshDatabase;

    private function streamedContent($response): string
    {
        ob_start();
        $response->baseResponse->sendContent();
        return ob_get_clean();
    }

    public function test_category_csv_contains_the_right_value_in_the_right_column(): void
    {
        $admin    = User::factory()->create(['role' => 'admin']);
        $site     = Site::factory()->create();
        $category = SiteCategory::factory()->create(['site_id' => $site->id, 'slug' => 'solar']);
        $param    = SiteParameter::factory()->create([
            'site_category_id' => $category->id, 'name' => 'Solar Output', 'unit' => 'kW', 'input_type' => 'sensor',
        ]);
        SensorReading::factory()->create([
            'site_id' => $site->id, 'site_parameter_id' => $param->id,
            'value' => 4.2, 'read_at' => now(),
        ]);

        $response = $this->actingAs($admin)->get(route('export.category.csv', [$site, 'solar']));
        $content  = $this->streamedContent($response);

        $this->assertStringContainsString('Solar Output (kW)', $content);
        $this->assertStringContainsString('4.2', $content);
        $this->assertStringContainsString('Sensor', $content);
    }

    public function test_category_csv_places_manual_and_sensor_values_in_separate_rows(): void
    {
        $admin    = User::factory()->create(['role' => 'admin']);
        $site     = Site::factory()->create();
        $category = SiteCategory::factory()->create(['site_id' => $site->id, 'slug' => 'agriculture']);
        $sensor   = SiteParameter::factory()->create([
            'site_category_id' => $category->id, 'name' => 'Soil Moisture', 'input_type' => 'sensor',
        ]);
        $manual   = SiteParameter::factory()->create([
            'site_category_id' => $category->id, 'name' => 'Crop Yield', 'input_type' => 'manual', 'data_type' => 'float',
        ]);
        SensorReading::factory()->create([
            'site_id' => $site->id, 'site_parameter_id' => $sensor->id, 'value' => 33.3, 'read_at' => now(),
        ]);
        $user = User::factory()->create();
        ManualReading::factory()->create([
            'site_id' => $site->id, 'site_parameter_id' => $manual->id, 'user_id' => $user->id,
            'value' => 88.8, 'reading_date' => now(),
        ]);

        $response = $this->actingAs($admin)->get(route('export.category.csv', [$site, 'agriculture']));
        $content  = $this->streamedContent($response);
        $rows     = array_values(array_filter(explode("\n", $content)));

        $sensorRow = collect($rows)->first(fn ($r) => str_contains($r, '33.3'));
        $manualRow = collect($rows)->first(fn ($r) => str_contains($r, '88.8'));

        $this->assertNotNull($sensorRow);
        $this->assertNotNull($manualRow);
        $this->assertStringEndsWith('Sensor', trim($sensorRow));
        $this->assertStringEndsWith('Manual', trim($manualRow));
        // Each row only carries its own value — the other type's column is blank.
        $this->assertStringNotContainsString('88.8', $sensorRow);
        $this->assertStringNotContainsString('33.3', $manualRow);
    }

    public function test_category_csv_only_includes_readings_within_the_requested_range(): void
    {
        $admin    = User::factory()->create(['role' => 'admin']);
        $site     = Site::factory()->create();
        $category = SiteCategory::factory()->create(['site_id' => $site->id, 'slug' => 'solar']);
        $param    = SiteParameter::factory()->create(['site_category_id' => $category->id, 'name' => 'Solar Output']);

        SensorReading::factory()->create([
            'site_id' => $site->id, 'site_parameter_id' => $param->id,
            'value' => 111.1, 'read_at' => '2026-07-03 12:00:00',
        ]);
        SensorReading::factory()->create([
            'site_id' => $site->id, 'site_parameter_id' => $param->id,
            'value' => 222.2, 'read_at' => '2026-07-10 12:00:00',
        ]);

        $response = $this->actingAs($admin)->get(
            route('export.category.csv', [$site, 'solar']) . '?from=2026-07-01&to=2026-07-05'
        );
        $content = $this->streamedContent($response);

        $this->assertStringContainsString('111.1', $content);
        $this->assertStringNotContainsString('222.2', $content);
    }

    public function test_all_csv_writes_one_section_per_category_with_a_header(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $site  = Site::factory()->create();

        $solar = SiteCategory::factory()->create(['site_id' => $site->id, 'name' => 'Solar', 'slug' => 'solar']);
        $water = SiteCategory::factory()->create(['site_id' => $site->id, 'name' => 'Water', 'slug' => 'water']);
        SiteParameter::factory()->create(['site_category_id' => $solar->id, 'name' => 'Solar Output']);
        SiteParameter::factory()->create(['site_category_id' => $water->id, 'name' => 'Borehole Level']);

        $response = $this->actingAs($admin)->get(route('export.all.csv', $site));
        $content  = $this->streamedContent($response);

        $this->assertStringContainsString('Solar', $content);
        $this->assertStringContainsString('Water', $content);
        $this->assertStringContainsString('Solar Output', $content);
        $this->assertStringContainsString('Borehole Level', $content);
    }

    public function test_csv_cell_starting_with_equals_sign_is_neutralized_against_formula_injection(): void
    {
        $admin    = User::factory()->create(['role' => 'admin']);
        $site     = Site::factory()->create();
        $category = SiteCategory::factory()->create(['site_id' => $site->id, 'slug' => 'agriculture']);
        $param    = SiteParameter::factory()->create([
            'site_category_id' => $category->id, 'input_type' => 'manual', 'data_type' => 'string',
        ]);
        $group    = SiteParameter::factory()->create([
            'site_category_id' => $category->id, 'input_type' => 'manual', 'group_name' => 'Notes',
        ]);
        $user = User::factory()->create();
        ManualReading::factory()->create([
            'site_id' => $site->id, 'site_parameter_id' => $group->id, 'user_id' => $user->id,
            'value' => 5, 'reading_date' => now(),
            'notes' => '=cmd|/c calc',
        ]);

        $response = $this->actingAs($admin)->get(
            route('export.group.csv', [$site, 'agriculture', 'Notes'])
        );
        $content = $this->streamedContent($response);

        // The raw formula-looking string must never appear unescaped — it
        // should always be preceded by the neutralizing leading apostrophe.
        $this->assertStringNotContainsString(",=cmd", $content);
        $this->assertStringContainsString("'=cmd", $content);
    }
}
