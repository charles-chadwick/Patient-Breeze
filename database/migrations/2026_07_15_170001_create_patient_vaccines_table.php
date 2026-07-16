<?php

use App\Models\Patient;
use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('patient_vaccines', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(Patient::class);
            $table->string('vaccine');
            $table->string('cvx_code')->nullable();
            $table->date('administered_on');
            $table->unsignedSmallInteger('dose_number')->nullable();
            $table->string('status')->default('Completed');
            $table->string('route')->nullable();
            $table->string('site')->nullable();
            $table->string('dose_amount')->nullable();
            $table->string('manufacturer')->nullable();
            $table->string('lot_number')->nullable();
            $table->date('expires_on')->nullable();
            $table->foreignIdFor(User::class, 'administered_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['patient_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('patient_vaccines');
    }
};
