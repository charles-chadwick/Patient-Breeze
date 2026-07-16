<?php

namespace Database\Seeders;

use App\Models\Vaccine;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\File;

class VaccineSeeder extends Seeder
{
    /**
     * Seed the vaccine catalog from the curated, CVX-coded JSON dataset.
     */
    public function run(): void
    {
        $now = Carbon::now();

        $vaccines = collect(File::json(database_path('data/vaccines.json')))
            ->map(fn (array $vaccine): array => [
                'name' => $vaccine['name'],
                'cvx_code' => $vaccine['cvx_code'],
                'created_at' => $now,
                'updated_at' => $now,
            ]);

        $vaccines->chunk(100)->each(function ($chunk): void {
            Vaccine::insert($chunk->all());
        });
    }
}
