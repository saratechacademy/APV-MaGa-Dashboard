<?php

namespace Database\Factories;

use App\Models\Site;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Site>
 */
class SiteFactory extends Factory
{
    protected $model = Site::class;

    public function definition(): array
    {
        return [
            'user_id'     => User::factory(),
            'name'        => fake()->city() . ' Agrivoltaic Site',
            'country'     => fake()->randomElement(['Senegal', 'Gambia', 'Mali', 'Ghana']),
            'latitude'    => fake()->latitude(-5, 20),
            'longitude'   => fake()->longitude(-20, 10),
            'capacity_kw' => fake()->randomFloat(1, 1, 50),
            'area_m2'     => fake()->numberBetween(200, 5000),
            'description' => fake()->sentence(),
        ];
    }
}
