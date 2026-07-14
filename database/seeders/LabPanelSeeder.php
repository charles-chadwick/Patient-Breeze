<?php

namespace Database\Seeders;

use App\Models\LabOrder;
use App\Models\LabPanel;
use Illuminate\Database\Seeder;

class LabPanelSeeder extends Seeder
{
    /**
     * Common lab panels, each grouping several individual lab orders by name.
     *
     * @var array<int, array{name: string, description: string, members: list<string>}>
     */
    private const PANELS = [
        [
            'name' => 'Basic Metabolic Panel (BMP)',
            'description' => 'Kidney function, blood glucose, and electrolyte balance.',
            'members' => [
                'Glucose, Fasting',
                'Blood Urea Nitrogen (BUN)',
                'Creatinine, Serum',
                'Sodium, Serum',
                'Potassium, Serum',
                'Chloride, Serum',
                'Carbon Dioxide (CO2), Serum',
                'Calcium, Serum',
            ],
        ],
        [
            'name' => 'Comprehensive Metabolic Panel (CMP)',
            'description' => 'Basic metabolic panel plus liver enzymes and proteins.',
            'members' => [
                'Glucose, Fasting',
                'Blood Urea Nitrogen (BUN)',
                'Creatinine, Serum',
                'Sodium, Serum',
                'Potassium, Serum',
                'Chloride, Serum',
                'Carbon Dioxide (CO2), Serum',
                'Calcium, Serum',
                'Albumin, Serum',
                'Total Protein, Serum',
                'Bilirubin, Total',
                'Alkaline Phosphatase (ALP)',
                'Alanine Aminotransferase (ALT)',
                'Aspartate Aminotransferase (AST)',
            ],
        ],
        [
            'name' => 'Hepatic Function Panel',
            'description' => 'Liver enzymes, bilirubin, and proteins to assess liver health.',
            'members' => [
                'Albumin, Serum',
                'Total Protein, Serum',
                'Bilirubin, Total',
                'Bilirubin, Direct',
                'Alkaline Phosphatase (ALP)',
                'Alanine Aminotransferase (ALT)',
                'Aspartate Aminotransferase (AST)',
            ],
        ],
        [
            'name' => 'Lipid Panel',
            'description' => 'Cholesterol and triglyceride measurements for cardiovascular risk.',
            'members' => [
                'Cholesterol, Total',
                'HDL Cholesterol',
                'LDL Cholesterol, Direct',
                'Triglycerides',
            ],
        ],
        [
            'name' => 'Thyroid Panel',
            'description' => 'Thyroid-stimulating hormone and thyroid hormone levels.',
            'members' => [
                'Thyroid Stimulating Hormone (TSH)',
                'Free Thyroxine (Free T4)',
                'Total Thyroxine (T4)',
                'Free Triiodothyronine (Free T3)',
            ],
        ],
        [
            'name' => 'Iron Studies Panel',
            'description' => 'Iron, storage, and binding capacity for anemia workup.',
            'members' => [
                'Iron, Total',
                'Total Iron Binding Capacity (TIBC)',
                'Ferritin',
                'Transferrin',
            ],
        ],
        [
            'name' => 'Coagulation Panel',
            'description' => 'Clotting function and bleeding risk assessment.',
            'members' => [
                'Prothrombin Time (PT/INR)',
                'Partial Thromboplastin Time (PTT)',
                'Fibrinogen',
            ],
        ],
        [
            'name' => 'Acute Hepatitis Panel',
            'description' => 'Serology screen for acute viral hepatitis infection.',
            'members' => [
                'Hepatitis B Surface Antigen',
                'Hepatitis B Surface Antibody',
                'Hepatitis C Antibody',
            ],
        ],
    ];

    /**
     * Seed common lab panels and attach their member lab orders.
     *
     * Runs after LabOrderSeeder so the member orders already exist.
     */
    public function run(): void
    {
        $lab_orders_by_name = LabOrder::query()->pluck('id', 'name');

        foreach (self::PANELS as $panel) {
            $lab_panel = LabPanel::create([
                'name' => $panel['name'],
                'description' => $panel['description'],
            ]);

            $member_ids = collect($panel['members'])
                ->map(fn (string $member_name): ?int => $lab_orders_by_name->get($member_name))
                ->filter()
                ->all();

            $lab_panel->labOrders()->sync($member_ids);
        }
    }
}
