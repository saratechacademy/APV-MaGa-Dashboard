<?php

namespace Database\Factories;

use App\Models\SensorReading;
use App\Models\Site;
use App\Models\SiteParameter;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SensorReading>
 */
class SensorReadingFactory extends Factory
{
    protected $model = SensorReading::class;

    public function definition(): array
    {
        return [
            'site_id'           => Site::factory(),
            'site_parameter_id' => SiteParameter::factory(),
            'value'             => fake()->randomFloat(2, 0, 100),
            'value_text'        => null,
            'read_at'           => fake()->dateTimeBetween('-1 day', 'now'),
        ];
    }
}
