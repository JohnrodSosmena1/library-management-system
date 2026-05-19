<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Change status column type to include all status values
        DB::statement("ALTER TABLE borrowings MODIFY status VARCHAR(20) NOT NULL DEFAULT 'Pending'");
    }

    public function down(): void
    {
        // Safely revert to VARCHAR to avoid ENUM constraint issues
        // The down() doesn't need to re-enforce limited ENUM values
        DB::statement("ALTER TABLE borrowings MODIFY status VARCHAR(20) NOT NULL DEFAULT 'Pending'");
    }
};
