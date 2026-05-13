<?php

use App\Enums\DiscussionPostStatus;
use App\Models\Discussion;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('discussion_posts', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(Discussion::class)->constrained()->cascadeOnDelete();
            $table->enum('status', array_column(DiscussionPostStatus::cases(), 'value'));
            $table->longText('content');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('discussion_posts');
    }
};
