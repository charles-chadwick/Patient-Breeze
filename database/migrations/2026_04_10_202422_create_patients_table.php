<?php

use App\Enums\GenderAtBirth;
use App\Enums\GenderIdentity;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('patients', function (Blueprint $table) {
            $table->id();
            $table->string('mrn')->unique();
            $table->string('prefix')->default('');
            $table->string('first_name')->default('');
            $table->string('middle_name')->default('');
            $table->string('last_name')->default('');
            $table->string('suffix')->default('');
            $table->string('email')->nullable()->unique();
            $table->string('password')->nullable();
            $table->rememberToken();
            $table->timestamp('email_verified_at')->nullable();
            $table->date('date_of_birth')->index();
            $table->enum('gender_at_birth', array_column(GenderAtBirth::cases(), 'value'));
            $table->enum('gender_identity', array_column(GenderIdentity::cases(), 'value'))->nullable();
            $table->string('blood_type')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('patients');
    }
};
