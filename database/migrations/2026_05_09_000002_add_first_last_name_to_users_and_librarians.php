<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // users
        Schema::table('users', function (Blueprint $table) {
            $table->string('first_name')->nullable()->after('name');
            $table->string('last_name')->nullable()->after('first_name');
        });

        // librarians
        Schema::table('librarians', function (Blueprint $table) {
            $table->string('first_name')->nullable()->after('name');
            $table->string('last_name')->nullable()->after('first_name');
        });

        // Backfill from existing `name`.
        // Simple split: first token => first_name, rest => last_name.
        // If only one token, last_name stays NULL.
        DB::table('users')->orderBy('id')->lazyById()->each(function ($row) {
            $parts = preg_split('/\s+/', trim($row->name ?? ''));
            $first = $parts[0] ?? null;
            $last = null;
            if (count($parts) > 1) {
                array_shift($parts);
                $last = implode(' ', $parts);
            }

            DB::table('users')
                ->where('id', $row->id)
                ->update([
                    'first_name' => $first,
                    'last_name'  => $last,
                ]);
        });

        DB::table('librarians')->orderBy('id')->lazyById()->each(function ($row) {
            $parts = preg_split('/\s+/', trim($row->name ?? ''));
            $first = $parts[0] ?? null;
            $last = null;
            if (count($parts) > 1) {
                array_shift($parts);
                $last = implode(' ', $parts);
            }

            DB::table('librarians')
                ->where('id', $row->id)
                ->update([
                    'first_name' => $first,
                    'last_name'  => $last,
                ]);
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['first_name', 'last_name']);
        });

        Schema::table('librarians', function (Blueprint $table) {
            $table->dropColumn(['first_name', 'last_name']);
        });
    }
};

