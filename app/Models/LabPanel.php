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
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Http\Request;

class LabPanel extends Model
{
    use Filterable, HasFactory, HasListing, Searchable, SoftDeletes, Sortable;

    protected $fillable = [
        'name',
        'description',
    ];

    /**
     * Lab orders grouped under this panel.
     *
     * @return BelongsToMany<LabOrder, $this>
     */
    public function labOrders(): BelongsToMany
    {
        return $this->belongsToMany(LabOrder::class)
            ->using(LabOrderLabPanel::class)
            ->withTimestamps()
            ->wherePivotNull('deleted_at');
    }

    public function scopeMatchingSearch(Builder $query, string $search): void
    {
        $query->where(fn (Builder $query) => $query
            ->where('name', 'like', "%{$search}%")
            ->orWhere('description', 'like', "%{$search}%")
        );
    }

    /**
     * @return array{search: string, sort_by: string, direction: string, filters: array<string, list<string>>}&array<string, mixed>
     */
    public function scopeListing(Builder $query, Request $request): array
    {
        return $this->paginatedListing($query, $request, 'lab_panels', 'name');
    }

    /** @return list<string> */
    protected function searchableFields(): array
    {
        return ['name', 'description'];
    }

    /** @return array<string, string> */
    protected function sortableFields(): array
    {
        return [
            'name' => 'name',
        ];
    }

    /** @return array<string, string> */
    protected function filterableFields(): array
    {
        return [];
    }
}
