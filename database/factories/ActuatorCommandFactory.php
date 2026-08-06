<?php

namespace Database\Factories;

use App\Models\ActuatorCommand;
use App\Models\Site;
use App\Models\SiteParameter;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ActuatorCommand>
 */
class ActuatorCommandFactory extends Factory
{
    protected $model = ActuatorCommand::class;

    public function definition(): array
    {
        return [
            'site_id'           => Site::factory(),
            'site_parameter_id' => SiteParameter::factory(),
            'desired_state'     => 0,
            'reported_state'    => 0,
            'reported_at'       => now(),
            'updated_by'        => null,
        ];
    }
}
