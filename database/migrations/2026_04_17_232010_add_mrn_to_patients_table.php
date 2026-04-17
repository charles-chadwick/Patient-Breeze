<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('patients', function (Blueprint $table) {
            $table->string('mrn')->nullable()->after('id');
        });

        DB::table('patients')->orderBy('id')->each(function ($patient) {
            DB::table('patients')
                ->where('id', $patient->id)
                ->update(['mrn' => 'MRN-'.str_pad((string) $patient->id, 7, '0', STR_PAD_LEFT)]);
        });

        Schema::table('patients', function (Blueprint $table) {
            $table->string('mrn')->nullable(false)->unique()->change();
        });
    }

    public function down(): void
    {
        Schema::table('patients', function (Blueprint $table) {
            $table->dropUnique(['mrn']);
            $table->dropColumn('mrn');
        });
    }
};
