<?php

namespace App\Models;

use App\Models\Concerns\Filterable;
use App\Models\Concerns\HasListing;
use App\Models\Concerns\Searchable;
use App\Models\Concerns\Sortable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class InsuranceCompany extends Model
{
    use Filterable, HasFactory, HasListing, Searchable, SoftDeletes, Sortable;

    protected $fillable = [
        'name',
        'payer_id',
        'address_line1',
        'address_line2',
        'city',
        'state',
        'postal_code',
        'phone',
        'fax',
        'website',
        'notes',
    ];

    /**
     * The patient policies issued under this company.
     */
    public function patientInsurances(): HasMany
    {
        return $this->hasMany(PatientInsurance::class);
    }

    public function scopeMatchingSearch(Builder $query, string $search): void
    {
        $query->where(fn (Builder $query) => $query
            ->where('name', 'like', "%{$search}%")
            ->orWhere('payer_id', 'like', "%{$search}%")
            ->orWhere('city', 'like', "%{$search}%")
        );
    }

    /**
     * A single-line rendering of the mailing address, skipping any blank parts.
     */
    public function addressLine(): ?string
    {
        $cityState = collect([$this->city, $this->state])->filter()->implode(', ');

        $parts = collect([
            $this->address_line1,
            $this->address_line2,
            collect([$cityState, $this->postal_code])->filter()->implode(' '),
        ])->filter();

        return $parts->isEmpty() ? null : $parts->implode(', ');
    }

    /**
     * Search the insurance catalog and shape the results for the picker modal.
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
            ->map(fn (InsuranceCompany $company): array => [
                'id' => $company->id,
                'name' => $company->name,
                'payer_id' => $company->payer_id,
                'address' => $company->addressLine(),
                'phone' => $company->phone,
            ]);
    }

    /**
     * @return array{search: string, sort_by: string, direction: string, filters: array<string, list<string>>}&array<string, mixed>
     */
    public function scopeListing(Builder $query, Request $request): array
    {
        return $this->paginatedListing($query, $request, 'insurance_companies', 'name');
    }

    /** @return list<string> */
    protected function searchableFields(): array
    {
        return ['name', 'payer_id', 'city'];
    }

    /** @return array<string, string> */
    protected function sortableFields(): array
    {
        return [
            'name' => 'name',
            'payer_id' => 'payer_id',
            'city' => 'city',
        ];
    }

    /** @return array<string, string> */
    protected function filterableFields(): array
    {
        return [];
    }
}
