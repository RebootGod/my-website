<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Drop index if exists (MySQL only)
        try {
            DB::statement('ALTER TABLE audit_logs DROP INDEX audit_logs_auditable_type_auditable_id_index');
        } catch (\Exception $e) {
            // Index does not exist, ignore
        }
    }

    public function down(): void
    {
        // No need to recreate the index
    }
};
