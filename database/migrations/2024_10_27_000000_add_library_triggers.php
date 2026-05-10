<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;


return new class extends Migration
{
    public function up(): void

    {
        // Only create triggers if required tables exist
        if (Schema::hasTable('books') && Schema::hasTable('borrowings')) {
            // Drop triggers if exist (idempotent)
            DB::unprepared('DROP TRIGGER IF EXISTS after_borrowing_insert');
            DB::unprepared('DROP TRIGGER IF EXISTS after_borrowing_return_update');
            DB::unprepared('DROP TRIGGER IF EXISTS before_borrowing_prevent_unavailable');

// Quantity triggers are handled in application code to avoid double-fire/mismatched quantities.
            // Disable quantity/status triggers here. (Logic is implemented in controller.)
            // 1) Trigger: Update Book Status when Borrowed (AFTER INSERT)
            // DB::unprepared("CREATE TRIGGER...");


// 2) Trigger: Update Book Status when Returned (AFTER UPDATE)
            // DB::unprepared("
                CREATE TRIGGER after_borrowing_return_update 
                AFTER UPDATE ON borrowings 
                FOR EACH ROW 
                BEGIN
                    IF NEW.status = 'Returned' AND OLD.status != 'Returned' THEN
                        UPDATE books 
                        SET quantity = quantity + 1,
                            status = 'Available'
                        WHERE id = NEW.book_id;
                    END IF;
                END
            ");


// 3) Trigger: Prevent Borrowing if Not Available (BEFORE INSERT) - MySQL only
            // DB::unprepared("
                CREATE TRIGGER before_borrowing_prevent_unavailable 
                BEFORE INSERT ON borrowings 
                FOR EACH ROW 
                BEGIN
                    IF NEW.status = 'Borrowed' AND 
                       (SELECT quantity FROM books WHERE id = NEW.book_id) <= 0 THEN
                        SIGNAL SQLSTATE '45000' 
                        SET MESSAGE_TEXT = 'Cannot borrow: Book is out of stock (quantity <= 0)';
                    END IF;
                END
            ");
        }
    }

    public function down(): void

    {
        DB::unprepared('DROP TRIGGER IF EXISTS after_borrowing_insert');
        DB::unprepared('DROP TRIGGER IF EXISTS after_borrowing_return_update');
        DB::unprepared('DROP TRIGGER IF EXISTS before_borrowing_prevent_unavailable');
    }
};

