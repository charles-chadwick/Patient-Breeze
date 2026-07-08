<?php

namespace App\Models;

use App\Enums\DoseForm;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Medication extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'type',
        'name',
        'dosage',
        'dose_form',
        'ndc',
    ];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'dose_form' => DoseForm::class,
        ];
    }

    public function scopeMatchingSearch(Builder $query, string $search): void
    {
        $query->where(fn (Builder $query) => $query
            ->where('name', 'like', "%{$search}%")
            ->orWhere('type', 'like', "%{$search}%")
            ->orWhere('ndc', 'like', "%{$search}%")
        );
    }
}
