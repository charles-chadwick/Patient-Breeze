<?php

use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('patients', function (Blueprint $table): void {
            $table->string('prefix')->default('');
            $table->string('first_name')->default('');
            $table->string('middle_name')->default('');
            $table->string('last_name')->default('');
            $table->string('suffix')->default('');
            $table->string('email')->nullable();
        });

        // Copy identity data from linked user records (no-op in test DB — no existing rows).
        DB::statement('
            UPDATE patients
            SET
                prefix      = (SELECT COALESCE(prefix, "")      FROM users WHERE id = patients.user_id),
                first_name  = (SELECT COALESCE(first_name, "")  FROM users WHERE id = patients.user_id),
                middle_name = (SELECT COALESCE(middle_name, "") FROM users WHERE id = patients.user_id),
                last_name   = (SELECT COALESCE(last_name, "")   FROM users WHERE id = patients.user_id),
                suffix      = (SELECT COALESCE(suffix, "")      FROM users WHERE id = patients.user_id),
                email       = (SELECT email                      FROM users WHERE id = patients.user_id)
            WHERE patients.user_id IS NOT NULL
        ');

        Schema::table('patients', function (Blueprint $table): void {
            $table->dropForeign(['user_id']);
            $table->dropColumn('user_id');
            $table->unique('email');
        });
    }

    public function down(): void
    {
        if (! Schema::hasColumn('patients', 'user_id')) {
            Schema::table('patients', function (Blueprint $table): void {
                $table->unsignedBigInteger('user_id')->nullable()->after('id');
            });
        }

        if (Schema::hasColumn('patients', 'email')) {
            DB::statement('
                UPDATE patients
                SET user_id = (SELECT id FROM users WHERE users.email = patients.email)
                WHERE patients.email IS NOT NULL
                  AND user_id IS NULL
            ');

            Schema::table('patients', function (Blueprint $table): void {
                $table->dropUnique(['email']);
                $table->dropColumn(['prefix', 'first_name', 'middle_name', 'last_name', 'suffix', 'email']);
            });
        }

        // Rows with no matching user cannot satisfy the FK — remove them before constraining.
        DB::table('patients')->whereNull('user_id')->delete();

        Schema::table('patients', function (Blueprint $table): void {
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
        });
    }
};
