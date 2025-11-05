<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('activity_log', function (Blueprint $table) {
            // Alter subject_id and causer_id to support both UUIDs and integers
            // Change to string type to support both UUID (string) and integer IDs
            DB::statement('ALTER TABLE activity_log ALTER COLUMN subject_id TYPE VARCHAR(36) USING subject_id::text');
            DB::statement('ALTER TABLE activity_log ALTER COLUMN causer_id TYPE VARCHAR(36) USING causer_id::text');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('activity_log', function (Blueprint $table) {
            // Revert back to bigint (may cause data loss if UUIDs were stored)
            // Only revert if no UUIDs were stored
            DB::statement('ALTER TABLE activity_log ALTER COLUMN subject_id TYPE BIGINT USING CASE WHEN subject_id ~ \'^[0-9]+$\' THEN subject_id::bigint ELSE NULL END');
            DB::statement('ALTER TABLE activity_log ALTER COLUMN causer_id TYPE BIGINT USING CASE WHEN causer_id ~ \'^[0-9]+$\' THEN causer_id::bigint ELSE NULL END');
        });
    }
};

