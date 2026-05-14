<?php

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
        Schema::table('discussions', function (Blueprint $table) {
            $table->unsignedBigInteger('discussionable_id')->change();
            $table->index(['discussionable_type', 'discussionable_id'], 'discussions_discussionable_index');
        });
    }

    public function down(): void
    {
        Schema::table('discussions', function (Blueprint $table) {
            $table->dropIndex('discussions_discussionable_index');
            $table->string('discussionable_id')->change();
        });
    }
};
