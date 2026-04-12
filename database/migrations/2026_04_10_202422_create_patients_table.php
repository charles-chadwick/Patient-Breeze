<?php

use App\Enums\GenderAtBirth;
use App\Enums\GenderIdentity;
use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('patients', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(User::class)
                ->constrained()
                ->cascadeOnDelete();
            $table->date('date_of_birth')->index();
            $table->enum('gender_at_birth', array_column(GenderAtBirth::cases(), 'value'));
            $table->enum('gender_identity', array_column(GenderIdentity::cases(), 'value'))
                ->nullable();
            $table->string('blood_type');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('patients');
    }
};
