<?php

namespace App\Rules;

use App\Models\Appointment;
use Closure;
use DateTime;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Translation\PotentiallyTranslatedString;

class AppointmentIsAvailableRule implements ValidationRule
{
    public function __construct(
        private readonly DateTime|string $start,
        private readonly DateTime|string $end,
        private readonly array $user_ids,
        private readonly ?int $exclude_id = null
    ) {}

    /**
     * Run the validation rule.
     *
     * @param  Closure(string, ?string=): PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail) : void
    {
        if ((new Appointment())->hasConflicts($this->start, $this->end, $this->user_ids, $this->exclude_id)) {
            $fail('The appointment time conflicts with another appointment.');
        }
    }
}
