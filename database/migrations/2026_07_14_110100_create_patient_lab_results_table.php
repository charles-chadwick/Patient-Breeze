<?php

use App\Models\LabOrder;
use App\Models\Patient;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('patient_lab_results', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(Patient::class);
            $table->foreignIdFor(LabOrder::class)->nullable()->nullOnDelete();
            // Snapshot of the ordered test, taken at result time.
            $table->string('name');
            $table->string('performing_lab');
            $table->string('cpt_code');
            // Stored as strings (numeric, qualitative, or boolean), cast on read.
            $table->string('value');
            $table->string('unit')->nullable();
            // Snapshot of the reference range resolved for the patient's gender/age.
            $table->string('reference_low')->nullable();
            $table->string('reference_high')->nullable();
            $table->string('reference_gender')->nullable();
            $table->unsignedSmallInteger('reference_age')->nullable();
            $table->date('collected_at')->nullable();
            $table->string('notes', 1000)->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('patient_lab_results');
    }
};
