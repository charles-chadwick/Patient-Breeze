<?php

namespace Database\Factories;

use App\Models\LabOrder;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

/**
 * @extends Factory<LabOrder>
 */
class LabOrderFactory extends Factory
{
    protected $model = LabOrder::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $performing_labs = [
            'Quest Diagnostics',
            'Labcorp',
            'Mayo Clinic Laboratories',
            'ARUP Laboratories',
            'BioReference Laboratories',
            'Hospital Core Laboratory',
        ];

        return [
            'name' => ucfirst($this->faker->words(3, true)),
            'performing_lab' => $this->faker->randomElement($performing_labs),
            'cpt_code' => (string) $this->faker->numberBetween(80000, 89999),
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ];
    }
}
