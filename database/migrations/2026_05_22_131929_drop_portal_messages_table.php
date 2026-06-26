<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('portal_messages');
    }

    public function down(): void
    {
        // Recreating the abandoned portal_messages table is intentionally unsupported.
    }
};
