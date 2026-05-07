<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() !== 'pgsql') {
            return;
        }

        DB::statement('ALTER TABLE works DROP CONSTRAINT IF EXISTS works_work_status_check');
        DB::statement("ALTER TABLE works ADD CONSTRAINT works_work_status_check CHECK (work_status IN ('draft','submitted','under_review','changes_requested','verified','disputed','approved','restricted'))");
    }

    public function down(): void
    {
        if (DB::getDriverName() !== 'pgsql') {
            return;
        }

        DB::statement('ALTER TABLE works DROP CONSTRAINT IF EXISTS works_work_status_check');
        DB::statement("ALTER TABLE works ADD CONSTRAINT works_work_status_check CHECK (work_status IN ('draft','submitted','under_review','verified','disputed','approved','restricted'))");
    }
};
