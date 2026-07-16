<?php

use App\Models\InsuranceCompany;
use App\Models\Patient;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('patient_insurances', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(Patient::class);
            $table->foreignIdFor(InsuranceCompany::class)->constrained()->restrictOnDelete();
            $table->string('member_id');
            $table->string('group_number')->nullable();
            $table->string('plan_type')->nullable();
            $table->string('priority')->default('Primary');
            $table->string('subscriber_name')->nullable();
            $table->string('relationship_to_subscriber')->default('Self');
            $table->date('effective_on')->nullable();
            $table->date('terminates_on')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['patient_id', 'priority']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('patient_insurances');
    }
};
