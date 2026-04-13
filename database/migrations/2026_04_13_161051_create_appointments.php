<?php

use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('appointments', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(User::class, 'user_id')->nullable();
            $table->foreignIdFor(User::class, 'patient_id')->nullable();
            $table->date('date');
            $table->time('start_time');
            $table->time('end_time');
            $table->string('status');
            $table->string('reason');
            $table->string('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('appointments');
    }
};
