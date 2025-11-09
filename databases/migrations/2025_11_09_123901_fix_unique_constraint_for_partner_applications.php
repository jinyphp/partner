<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Fix unique constraint to allow reapplications
     *
     * Drop the overly restrictive unique index on user_id that prevents any user
     * from having multiple applications. Replace with a partial unique index that
     * only prevents multiple active applications.
     */
    public function up(): void
    {
        // Drop the existing unique constraint that prevents reapplications
        DB::statement('DROP INDEX IF EXISTS "unique_active_application"');

        // Create a new partial unique index that only prevents multiple active applications
        // This allows users to have rejected applications and then create new reapplications
        DB::statement('
            CREATE UNIQUE INDEX "unique_active_application_per_user"
            ON "partner_applications" ("user_id")
            WHERE "application_status" IN (\'submitted\', \'reviewing\', \'interview\', \'approved\')
            AND "deleted_at" IS NULL
        ');

        // Also create an index to help with reapplication queries
        DB::statement('
            CREATE INDEX "partner_applications_user_id_status_deleted_at"
            ON "partner_applications" ("user_id", "application_status", "deleted_at")
        ');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Drop the new indices
        DB::statement('DROP INDEX IF EXISTS "unique_active_application_per_user"');
        DB::statement('DROP INDEX IF EXISTS "partner_applications_user_id_status_deleted_at"');

        // Recreate the original constraint (this may fail if there are multiple applications)
        try {
            DB::statement('CREATE UNIQUE INDEX "unique_active_application" on "partner_applications" ("user_id")');
        } catch (\Exception $e) {
            // Log the error but don't fail the rollback
            \Log::warning('Could not recreate original unique constraint due to existing data: ' . $e->getMessage());
        }
    }
};
