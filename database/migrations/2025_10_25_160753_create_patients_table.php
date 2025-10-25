<?php

use App\Enums\Gender;
use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up() : void
    {
        Schema::create('patients', function (Blueprint $table) {
            $table->id();
            $table->string('status');
            $table->string('prefix')
                ->nullable();
            $table->string('first_name');
            $table->string('middle_name')
                ->nullable();
            $table->string('last_name');
            $table->string('suffix')
                ->nullable();
            $table->date('dob');
            $table->enum('gender', Gender::cases());
            $table->string('gender_identity')
                ->nullable();
            $table->string('email')
                ->nullable();
            $table->string('password')
                ->nullable();
            $table->foreignIdFor(User::class, 'created_by_id')->default(1);
            $table->foreignIdFor(User::class, 'updated_by_id')->nullable();
            $table->foreignIdFor(User::class, 'deleted_by_id')->nullable();
            $table->rememberToken();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down() : void
    {
        Schema::dropIfExists('patients');
    }
};
