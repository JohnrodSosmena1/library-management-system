<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Modify users table to match the app's User model structure
        Schema::table('users', function (Blueprint $table) {
            // Make password nullable since users (patrons) don't authenticate
            $table->string('password')->nullable()->change();
            
            // Add library patron fields if not exists
            if (!Schema::hasColumn('users', 'contact_no')) {
                $table->string('contact_no')->nullable()->after('email');
            }
            
            if (!Schema::hasColumn('users', 'status')) {
                $table->enum('status', ['Active', 'Inactive'])->default('Active')->after('contact_no');
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Don't try to change password to not nullable on rollback
            // as there may be NULL values from patrons
        });
    }
};
