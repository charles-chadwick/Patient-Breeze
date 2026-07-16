<?php

namespace Database\Seeders;

use App\Models\InsuranceCompany;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\File;

class InsuranceCompanySeeder extends Seeder
{
    /**
     * Seed the insurance-company catalog from the curated JSON dataset.
     */
    public function run(): void
    {
        $now = Carbon::now();

        $companies = collect(File::json(database_path('data/insurance_companies.json')))
            ->map(fn (array $company): array => [
                'name' => $company['name'],
                'payer_id' => $company['payer_id'] ?? null,
                'address_line1' => $company['address_line1'] ?? null,
                'address_line2' => $company['address_line2'] ?? null,
                'city' => $company['city'] ?? null,
                'state' => $company['state'] ?? null,
                'postal_code' => $company['postal_code'] ?? null,
                'phone' => $company['phone'] ?? null,
                'fax' => $company['fax'] ?? null,
                'website' => $company['website'] ?? null,
                'notes' => $company['notes'] ?? null,
                'created_at' => $now,
                'updated_at' => $now,
            ]);

        $companies->chunk(100)->each(function ($chunk): void {
            InsuranceCompany::insert($chunk->all());
        });
    }
}
