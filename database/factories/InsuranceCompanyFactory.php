<?php

namespace Database\Factories;

use App\Models\InsuranceCompany;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

/**
 * @extends Factory<InsuranceCompany>
 */
class InsuranceCompanyFactory extends Factory
{
    protected $model = InsuranceCompany::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => $this->faker->unique()->company().' Health',
            'payer_id' => strtoupper($this->faker->unique()->bothify('?####')),
            'address_line1' => $this->faker->streetAddress(),
            'address_line2' => $this->faker->boolean(20) ? $this->faker->secondaryAddress() : null,
            'city' => $this->faker->city(),
            'state' => $this->faker->stateAbbr(),
            'postal_code' => $this->faker->postcode(),
            'phone' => $this->faker->phoneNumber(),
            'fax' => $this->faker->boolean(40) ? $this->faker->phoneNumber() : null,
            'website' => $this->faker->boolean(60) ? $this->faker->url() : null,
            'notes' => $this->faker->boolean(20) ? $this->faker->sentence() : null,
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ];
    }
}
