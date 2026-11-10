<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class OrganizationFactory extends Factory
{
    public function definition(): array
    {
        return ['name' => $this->faker->company().' (Fictional)', 'kind' => 'garage', 'location' => 'Nairobi', 'status' => 'approved', 'description' => 'Fictional test provider', 'verification_scope' => 'Test-only identity check', 'verified_at' => now()];
    }
}
