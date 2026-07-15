<?php

namespace App\Models;

use App\Enums\AllergenCategory;
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

class Allergen extends Model
{
    use Filterable, HasFactory, HasListing, Searchable, SoftDeletes, Sortable;

    protected $fillable = [
        'name',
        'category',
    ];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'category' => AllergenCategory::class,
        ];
    }

    public function scopeMatchingSearch(Builder $query, string $search): void
    {
        $query->where(fn (Builder $query) => $query
            ->where('name', 'like', "%{$search}%")
            ->orWhere('category', 'like', "%{$search}%")
        );
    }

    /**
     * Search the allergen catalog and shape the results for the picker modal.
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
            ->map(fn (Allergen $allergen): array => [
                'id' => $allergen->id,
                'name' => $allergen->name,
                'category' => $allergen->category->value,
                'category_label' => $allergen->category->label(),
            ]);
    }

    /**
     * @return array{search: string, sort_by: string, direction: string, filters: array<string, list<string>>}&array<string, mixed>
     */
    public function scopeListing(Builder $query, Request $request): array
    {
        return $this->paginatedListing($query, $request, 'allergens', 'name');
    }

    /** @return list<string> */
    protected function searchableFields(): array
    {
        return ['name', 'category'];
    }

    /** @return array<string, string> */
    protected function sortableFields(): array
    {
        return [
            'name' => 'name',
            'category' => 'category',
        ];
    }

    /** @return array<string, string> */
    protected function filterableFields(): array
    {
        return [];
    }
}
