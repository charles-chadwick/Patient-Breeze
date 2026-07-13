<?php

use App\Models\Patient;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('patient_diagnoses', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(Patient::class);
            $table->string('diagnosis');
            $table->string('icd10_code');
            $table->date('diagnosed_on')->nullable();
            $table->string('status')->default('Active');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('patient_diagnoses');
    }
};
