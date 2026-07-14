<?php

namespace App\Models;

use App\Casts\FlexibleValue;
use App\Enums\GenderAtBirth;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class LabReferenceRange extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'lab_order_id',
        'gender_at_birth',
        'min_age',
        'max_age',
        'low_value',
        'high_value',
        'unit',
    ];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'gender_at_birth' => GenderAtBirth::class,
            'min_age' => 'integer',
            'max_age' => 'integer',
            'low_value' => FlexibleValue::class,
            'high_value' => FlexibleValue::class,
        ];
    }

    /**
     * @return BelongsTo<LabOrder, $this>
     */
    public function labOrder(): BelongsTo
    {
        return $this->belongsTo(LabOrder::class);
    }

    /**
     * A human-friendly rendering of the range, e.g. "13.5–17.5 g/dL", "< 200 mg/dL".
     */
    public function label(): string
    {
        $unit = $this->unit !== '' ? ' '.$this->unit : '';
        $low = $this->getRawOriginal('low_value');
        $high = $this->getRawOriginal('high_value');

        if ($low !== null && $high !== null) {
            return "{$low}–{$high}{$unit}";
        }

        if ($high !== null) {
            return "< {$high}{$unit}";
        }

        if ($low !== null) {
            return "> {$low}{$unit}";
        }

        return __('lab_results.no_range');
    }
}
