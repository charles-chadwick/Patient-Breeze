<?php

namespace App\Models;

use App\Models\Concerns\Filterable;
use App\Models\Concerns\HasListing;
use App\Models\Concerns\Searchable;
use App\Models\Concerns\Sortable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class LabOrder extends Model
{
    use Filterable, HasFactory, HasListing, Searchable, SoftDeletes, Sortable;

    protected $fillable = [
        'name',
        'performing_lab',
        'cpt_code',
    ];

    /**
     * Panels this lab order is grouped into.
     *
     * @return BelongsToMany<LabPanel, $this>
     */
    public function labPanels(): BelongsToMany
    {
        return $this->belongsToMany(LabPanel::class)
            ->using(LabOrderLabPanel::class)
            ->withTimestamps()
            ->wherePivotNull('deleted_at');
    }

    /**
     * Reference ranges for this test, varying by gender and age band.
     *
     * @return HasMany<LabReferenceRange, $this>
     */
    public function referenceRanges(): HasMany
    {
        return $this->hasMany(LabReferenceRange::class);
    }

    /**
     * Resolve the most specific reference range that applies to a patient's
     * gender-at-birth and current age. Gender- and age-specific ranges win over
     * catch-all rows. Returns null when no range matches.
     */
    public function resolveReferenceRangeFor(Patient $patient): ?LabReferenceRange
    {
        $gender = $patient->gender_at_birth;
        $age = $patient->currentAge();

        return $this->referenceRanges()
            ->where(function (Builder $query) use ($gender): void {
                $query->whereNull('gender_at_birth');

                if ($gender !== null) {
                    $query->orWhere('gender_at_birth', $gender->value);
                }
            })
            ->where(function (Builder $query) use ($age): void {
                $query->whereNull('min_age');

                if ($age !== null) {
                    $query->orWhere('min_age', '<=', $age);
                }
            })
            ->where(function (Builder $query) use ($age): void {
                $query->whereNull('max_age');

                if ($age !== null) {
                    $query->orWhere('max_age', '>=', $age);
                }
            })
            ->orderByRaw('gender_at_birth is null')
            ->orderByRaw('min_age is null')
            ->orderByRaw('max_age is null')
            ->first();
    }

    /**
     * This test's reference ranges ordered from most general to most specific
     * and shaped for the lab order form's ranges table.
     *
     * @return Collection<int, array<string, mixed>>
     */
    public function orderedReferenceRanges(): Collection
    {
        return $this->referenceRanges()
            ->orderByRaw('gender_at_birth is null')
            ->orderBy('gender_at_birth')
            ->orderByRaw('min_age is null')
            ->orderBy('min_age')
            ->get()
            ->map(fn (LabReferenceRange $range): array => [
                'id' => $range->id,
                'gender_at_birth' => $range->gender_at_birth?->value,
                'min_age' => $range->min_age,
                'max_age' => $range->max_age,
                'low_value' => $range->getRawOriginal('low_value'),
                'high_value' => $range->getRawOriginal('high_value'),
                'unit' => $range->unit,
                'label' => $range->label(),
            ]);
    }

    public function scopeMatchingSearch(Builder $query, string $search): void
    {
        $query->where(fn (Builder $query) => $query
            ->where('name', 'like', "%{$search}%")
            ->orWhere('performing_lab', 'like', "%{$search}%")
            ->orWhere('cpt_code', 'like', "%{$search}%")
        );
    }

    /**
     * Search the lab order catalog and shape the results for the picker modal.
     *
     * @return Collection<int, array<string, mixed>>
     */
    public function scopeSearchCatalog(Builder $query, string $search): Collection
    {
        return $query
            ->when($search !== '', fn (Builder $query) => $query->matchingSearch($search))
            ->orderBy('name')
            ->limit(20)
            ->get()
            ->map(fn (LabOrder $labOrder): array => [
                'id' => $labOrder->id,
                'name' => $labOrder->name,
                'performing_lab' => $labOrder->performing_lab,
                'cpt_code' => $labOrder->cpt_code,
            ]);
    }

    /**
     * @return array{search: string, sort_by: string, direction: string, filters: array<string, list<string>>}&array<string, mixed>
     */
    public function scopeListing(Builder $query, Request $request): array
    {
        return $this->paginatedListing($query, $request, 'lab_orders', 'name');
    }

    /** @return list<string> */
    protected function searchableFields(): array
    {
        return ['name', 'performing_lab', 'cpt_code'];
    }

    /** @return array<string, string> */
    protected function sortableFields(): array
    {
        return [
            'name' => 'name',
            'performing_lab' => 'performing_lab',
            'cpt_code' => 'cpt_code',
        ];
    }

    /** @return array<string, string> */
    protected function filterableFields(): array
    {
        return [];
    }
}
