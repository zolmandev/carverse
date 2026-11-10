<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class VehicleFactory extends Factory
{
    public function definition(): array
    {
        return ['user_id' => User::factory(), 'make' => 'Toyota', 'model' => 'Fielder', 'year' => 2016, 'variant' => 'Hybrid', 'engine_code' => '1NZ-FXE', 'fuel_type' => 'Hybrid', 'transmission' => 'CVT', 'identifier' => 'NKE165-'.$this->faker->numerify('#######'), 'registration' => 'KDE '.$this->faker->numerify('###').'D', 'mileage' => 87000, 'mileage_date' => today()];
    }
}
