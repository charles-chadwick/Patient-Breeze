<?php

use App\Models\Appointment;
use App\Models\Patient;
use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('patient_vitals', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(Patient::class);
            $table->foreignIdFor(Appointment::class)->nullable()->constrained()->nullOnDelete();
            $table->foreignIdFor(User::class, 'recorded_by')->nullable()->constrained('users')->nullOnDelete();

            $table->dateTime('measured_at');

            // Blood pressure, stored as a paired reading.
            $table->unsignedSmallInteger('systolic')->nullable();
            $table->unsignedSmallInteger('diastolic')->nullable();
            $table->string('position')->nullable();

            $table->unsignedSmallInteger('heart_rate')->nullable();
            $table->unsignedSmallInteger('respiratory_rate')->nullable();

            // Canonical units: temperature °C, weight kg, height cm.
            $table->decimal('temperature', 4, 1)->nullable();
            $table->string('temperature_site')->nullable();

            $table->unsignedTinyInteger('oxygen_saturation')->nullable();
            $table->string('oxygen_delivery')->nullable();

            $table->decimal('weight', 5, 2)->nullable();
            $table->decimal('height', 5, 2)->nullable();

            $table->unsignedTinyInteger('pain_score')->nullable();

            $table->text('notes')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index(['patient_id', 'measured_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('patient_vitals');
    }
};
