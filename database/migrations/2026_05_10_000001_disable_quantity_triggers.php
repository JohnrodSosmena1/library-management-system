<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
      
        if (!Schema::hasTable('books') || !Schema::hasTable('borrowings')) {
            return;
        }
    }

    public function down(): void
    {
    }
};

