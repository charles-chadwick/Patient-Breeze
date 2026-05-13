<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('discussion_participants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('discussion_id')->constrained()->cascadeOnDelete();
            $table->morphs('participantable', 'dp_participantable_index');
            $table->timestamp('seen_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('discussion_participants');
    }
};
