<?php

namespace Database\Seeders;

use App\Models\LabOrder;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\File;

class LabOrderSeeder extends Seeder
{
    /**
     * Seed the lab order catalog from the curated JSON dataset.
     */
    public function run(): void
    {
        $now = Carbon::now();

        $lab_orders = collect(File::json(database_path('data/lab_orders.json')))
            ->map(fn (array $lab_order): array => [
                'name' => $lab_order['name'],
                'performing_lab' => $lab_order['performing_lab'],
                'cpt_code' => $lab_order['cpt_code'],
                'created_at' => $now,
                'updated_at' => $now,
            ]);

        $lab_orders->chunk(100)->each(function ($chunk): void {
            LabOrder::insert($chunk->all());
        });
    }
}
