<?php

namespace Database\Factories;

use App\Models\SiteCategory;
use App\Models\SiteParameter;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<SiteParameter>
 */
class SiteParameterFactory extends Factory
{
    protected $model = SiteParameter::class;

    public function definition(): array
    {
        $name = fake()->unique()->words(2, true);

        return [
            'site_category_id'    => SiteCategory::factory(),
            'name'                => $name,
            'slug'                => Str::slug($name, '_'),
            'unit'                => 'kW',
            'data_type'           => 'float',
            'input_type'          => 'sensor',
            'control_type'        => 'readonly',
            'group_name'          => null,
            'site_parameter_group_id' => null,
            'min_value'           => null,
            'max_value'           => null,
            'warning_threshold'   => null,
            'critical_threshold'  => null,
            'threshold_direction' => 'below',
            'description'         => null,
            'is_active'           => true,
            'show_on_dashboard'   => true,
            'sort_order'          => 0,
        ];
    }

    public function manual(): static
    {
        return $this->state(fn () => ['input_type' => 'manual']);
    }

    public function switch(): static
    {
        return $this->state(fn () => [
            'data_type'    => 'switch',
            'input_type'   => 'sensor',
            'control_type' => 'controllable',
        ]);
    }
}
