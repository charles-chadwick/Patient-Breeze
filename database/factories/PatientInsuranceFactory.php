<?php

namespace Database\Factories;

use App\Enums\InsurancePlanType;
use App\Enums\InsurancePriority;
use App\Enums\SubscriberRelationship;
use App\Models\InsuranceCompany;
use App\Models\Patient;
use App\Models\PatientInsurance;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

/**
 * @extends Factory<PatientInsurance>
 */
class PatientInsuranceFactory extends Factory
{
    protected $model = PatientInsurance::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'patient_id' => Patient::factory(),
            'insurance_company_id' => InsuranceCompany::factory(),
            'member_id' => strtoupper($this->faker->bothify('???########')),
            'group_number' => strtoupper($this->faker->bothify('GRP-#####')),
            'plan_type' => $this->faker->randomElement(InsurancePlanType::cases()),
            'priority' => InsurancePriority::Primary,
            'subscriber_name' => $this->faker->name(),
            'relationship_to_subscriber' => SubscriberRelationship::Self,
            'effective_on' => Carbon::parse($this->faker->dateTimeBetween('-3 years', 'now'))->toDateString(),
            'terminates_on' => null,
            'notes' => $this->faker->boolean(15) ? $this->faker->sentence() : null,
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ];
    }

    public function priority(InsurancePriority $priority): self
    {
        return $this->state(fn (): array => ['priority' => $priority]);
    }
}
