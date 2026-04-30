<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Convert rows where patient_id is currently a users.id value.
        // Rows already storing a patients.id (from BookAppointmentAction) are
        // not matched by the subquery and are left unchanged — both cases resolve
        // to a valid patients.id after this runs.
        DB::statement('
            UPDATE appointments
            SET patient_id = (SELECT id FROM patients WHERE user_id = patient_id)
            WHERE patient_id IS NOT NULL
              AND EXISTS (SELECT 1 FROM patients WHERE user_id = patient_id)
        ');

        Schema::table('appointments', function (Blueprint $table): void {
            $table->foreign('patient_id')
                ->references('id')
                ->on('patients')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('appointments', function (Blueprint $table): void {
            $table->dropForeign(['patient_id']);
        });

        // Reverse: convert patients.id back to users.id
        DB::statement('
            UPDATE appointments
            SET patient_id = (SELECT user_id FROM patients WHERE id = patient_id)
            WHERE patient_id IS NOT NULL
              AND EXISTS (SELECT 1 FROM patients WHERE id = patient_id)
        ');
    }
};
