<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('borrowings', function (Blueprint $table) {
            // Rename borrow_date to date_borrowed
            $table->renameColumn('borrow_date', 'date_borrowed');
        });

        Schema::table('borrowings', function (Blueprint $table) {
            // Add missing librarian_id foreign key (nullable for pending requests)
            $table->foreignId('librarian_id')->nullable()->after('book_id')->constrained('librarians')->onDelete('restrict');
            
            // Add penalty field for late returns
            $table->decimal('penalty', 10, 2)->after('return_date')->default(0);
        });
    }

    public function down(): void
    {
        Schema::table('borrowings', function (Blueprint $table) {
            // Remove the added columns
            $table->dropForeign(['librarian_id']);
            $table->dropColumn(['librarian_id', 'penalty']);
        });

        Schema::table('borrowings', function (Blueprint $table) {
            // Rename date_borrowed back to borrow_date
            $table->renameColumn('date_borrowed', 'borrow_date');
        });
    }
};
