<?php

namespace Database\Factories;

use App\Models\Site;
use App\Models\SiteCategory;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<SiteCategory>
 */
class SiteCategoryFactory extends Factory
{
    protected $model = SiteCategory::class;

    public function definition(): array
    {
        $name = fake()->randomElement(['Solar', 'Water', 'Irrigation', 'Weather', 'Agriculture']);

        return [
            'site_id'                  => Site::factory(),
            'name'                     => $name,
            'slug'                     => Str::slug($name),
            'icon'                     => '☀',
            'color'                    => '#15803d',
            'description'              => null,
            'is_active'                => true,
            'sort_order'               => 0,
            'offline_threshold_minutes' => 5,
        ];
    }
}
