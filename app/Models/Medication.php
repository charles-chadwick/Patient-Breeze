<?php

namespace App\Models;

use App\Enums\DoseForm;
use App\Models\Concerns\Filterable;
use App\Models\Concerns\HasListing;
use App\Models\Concerns\Searchable;
use App\Models\Concerns\Sortable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class Medication extends Model
{
    use Filterable, HasFactory, HasListing, Searchable, SoftDeletes, Sortable;

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

    /**
     * Search the medication catalog and shape the results for the picker modal.
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
            ->map(fn (Medication $medication): array => [
                'id' => $medication->id,
                'type' => $medication->type,
                'name' => $medication->name,
                'dosage' => $medication->dosage,
                'dose_form' => $medication->dose_form->value,
                'ndc' => $medication->ndc,
            ]);
    }

    public function scopeListing(Builder $query, Request $request): array
    {
        return $this->paginatedListing($query, $request, 'medications', 'name');
    }

    /** @return list<string> */
    protected function searchableFields(): array
    {
        return ['name', 'type', 'ndc'];
    }

    /** @return array<string, string> */
    protected function sortableFields(): array
    {
        return [
            'name' => 'name',
            'type' => 'type',
            'dose_form' => 'dose_form',
            'ndc' => 'ndc',
        ];
    }

    /** @return array<string, string> */
    protected function filterableFields(): array
    {
        return ['dose_form' => 'dose_form'];
    }
}
