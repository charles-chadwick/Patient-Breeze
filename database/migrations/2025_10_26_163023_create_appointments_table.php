<?php

use App\Models\Patient;
use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up() : void
    {
        Schema::create('appointments', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(Patient::class);
            $table->string('type');
            $table->dateTime('start');
            $table->dateTime('end');
            $table->string('status');
            $table->string('title');
            $table->text('description')->nullable();
            $table->foreignIdFor(User::class, 'created_by_id')->default(1);
            $table->foreignIdFor(User::class, 'updated_by_id')->nullable();
            $table->foreignIdFor(User::class, 'deleted_by_id')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down() : void
    {
        Schema::dropIfExists('appointments');
    }
};
