<?php

use App\Models\LabOrder;
use App\Models\LabPanel;
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
        Schema::create('lab_order_lab_panel', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(LabPanel::class)->constrained()->cascadeOnDelete();
            $table->foreignIdFor(LabOrder::class)->constrained()->cascadeOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['lab_panel_id', 'lab_order_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('lab_order_lab_panel');
    }
};
