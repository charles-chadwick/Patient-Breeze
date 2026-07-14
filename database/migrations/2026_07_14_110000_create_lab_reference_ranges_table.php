<?php

use App\Models\LabOrder;
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
        Schema::create('lab_reference_ranges', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(LabOrder::class)->constrained()->cascadeOnDelete();
            // Null gender / age bounds mean the range applies to everyone / any age.
            $table->string('gender_at_birth')->nullable();
            $table->unsignedSmallInteger('min_age')->nullable();
            $table->unsignedSmallInteger('max_age')->nullable();
            // Stored as strings so any kind of bound can be expressed (numeric,
            // threshold, qualitative). Cast back to a natural type on read.
            $table->string('low_value')->nullable();
            $table->string('high_value')->nullable();
            $table->string('unit');
            $table->timestamps();
            $table->softDeletes();

            $table->index(['lab_order_id', 'gender_at_birth']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('lab_reference_ranges');
    }
};
