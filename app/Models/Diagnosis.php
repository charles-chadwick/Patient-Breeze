<?php

namespace App\Models;

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

class Diagnosis extends Model
{
    use Filterable, HasFactory, HasListing, Searchable, SoftDeletes, Sortable;

    protected $fillable = [
        'diagnosis',
        'icd10_code',
    ];

    public function scopeMatchingSearch(Builder $query, string $search): void
    {
        $query->where(fn (Builder $query) => $query
            ->where('diagnosis', 'like', "%{$search}%")
            ->orWhere('icd10_code', 'like', "%{$search}%")
        );
    }

    /**
     * Search the diagnosis catalog and shape the results for the picker modal.
     *
     * @return Collection<int, array<string, mixed>>
     */
    public function scopeSearchCatalog(Builder $query, string $search): Collection
    {
        return $query
            ->when($search !== '', fn (Builder $query) => $query->matchingSearch($search))
            ->orderBy('diagnosis')
            ->limit(20)
            ->get()
            ->map(fn (Diagnosis $diagnosis): array => [
                'id' => $diagnosis->id,
                'diagnosis' => $diagnosis->diagnosis,
                'icd10_code' => $diagnosis->icd10_code,
            ]);
    }

    /**
     * @return array{search: string, sort_by: string, direction: string, filters: array<string, list<string>>}&array<string, mixed>
     */
    public function scopeListing(Builder $query, Request $request): array
    {
        return $this->paginatedListing($query, $request, 'diagnoses', 'diagnosis');
    }

    /** @return list<string> */
    protected function searchableFields(): array
    {
        return ['diagnosis', 'icd10_code'];
    }

    /** @return array<string, string> */
    protected function sortableFields(): array
    {
        return [
            'diagnosis' => 'diagnosis',
            'icd10_code' => 'icd10_code',
        ];
    }

    /** @return array<string, string> */
    protected function filterableFields(): array
    {
        return [];
    }
}
