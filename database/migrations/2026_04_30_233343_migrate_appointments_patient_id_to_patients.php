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
        // not matched by this join and are left unchanged — both cases resolve
        // to a valid patients.id after this runs.
        DB::statement('
            UPDATE appointments a
            INNER JOIN patients p ON p.user_id = a.patient_id
            SET a.patient_id = p.id
            WHERE a.patient_id IS NOT NULL
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
            UPDATE appointments a
            INNER JOIN patients p ON p.id = a.patient_id
            SET a.patient_id = p.user_id
            WHERE a.patient_id IS NOT NULL
        ');
    }
};
