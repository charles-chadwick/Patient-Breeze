<?php

use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Records when a patient's allergy list was last reviewed, and by whom. This
     * is what separates "no known allergies" (reviewed, list empty) from "nobody
     * has asked yet" (never reviewed, list empty) on the chart.
     */
    public function up(): void
    {
        Schema::table('patients', function (Blueprint $table) {
            $table->timestamp('allergies_reviewed_at')->nullable()->after('blood_type');
            $table->foreignIdFor(User::class, 'allergies_reviewed_by')
                ->nullable()
                ->after('allergies_reviewed_at')
                ->constrained('users')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('patients', function (Blueprint $table) {
            $table->dropConstrainedForeignId('allergies_reviewed_by');
            $table->dropColumn('allergies_reviewed_at');
        });
    }
};
