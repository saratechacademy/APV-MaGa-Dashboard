<?php

namespace Database\Factories;

use App\Models\ManualReading;
use App\Models\Site;
use App\Models\SiteParameter;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ManualReading>
 */
class ManualReadingFactory extends Factory
{
    protected $model = ManualReading::class;

    public function definition(): array
    {
        return [
            'site_id'           => Site::factory(),
            'site_parameter_id' => SiteParameter::factory(),
            'user_id'           => User::factory(),
            'value'             => fake()->randomFloat(2, 0, 100),
            'reading_date'      => fake()->dateTimeBetween('-7 days', 'now'),
            'notes'             => null,
        ];
    }
}
