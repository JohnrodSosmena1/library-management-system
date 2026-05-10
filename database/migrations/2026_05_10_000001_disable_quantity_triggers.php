<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Remove quantity/status triggers to avoid double increment/decrement.
        if (!Schema::hasTable('books') || !Schema::hasTable('borrowings')) {
            return;
        }

        DB::unprepared('DROP TRIGGER IF EXISTS after_borrowing_insert');
        DB::unprepared('DROP TRIGGER IF EXISTS after_borrowing_return_update');
        DB::unprepared('DROP TRIGGER IF EXISTS before_borrowing_prevent_unavailable');
    }

    public function down(): void
    {
        // Recreate is intentionally not done here.
        // Quantity is managed in application code.
    }
};

