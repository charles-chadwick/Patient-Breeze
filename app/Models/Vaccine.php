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

class Vaccine extends Model
{
    use Filterable, HasFactory, HasListing, Searchable, SoftDeletes, Sortable;

    protected $fillable = [
        'name',
        'cvx_code',
    ];

    public function scopeMatchingSearch(Builder $query, string $search): void
    {
        $query->where(fn (Builder $query) => $query
            ->where('name', 'like', "%{$search}%")
            ->orWhere('cvx_code', 'like', "%{$search}%")
        );
    }

    /**
     * Search the vaccine catalog and shape the results for the picker modal.
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
            ->map(fn (Vaccine $vaccine): array => [
                'id' => $vaccine->id,
                'name' => $vaccine->name,
                'cvx_code' => $vaccine->cvx_code,
            ]);
    }

    /**
     * @return array{search: string, sort_by: string, direction: string, filters: array<string, list<string>>}&array<string, mixed>
     */
    public function scopeListing(Builder $query, Request $request): array
    {
        return $this->paginatedListing($query, $request, 'vaccines', 'name');
    }

    /** @return list<string> */
    protected function searchableFields(): array
    {
        return ['name', 'cvx_code'];
    }

    /** @return array<string, string> */
    protected function sortableFields(): array
    {
        return [
            'name' => 'name',
            'cvx_code' => 'cvx_code',
        ];
    }

    /** @return array<string, string> */
    protected function filterableFields(): array
    {
        return [];
    }
}
