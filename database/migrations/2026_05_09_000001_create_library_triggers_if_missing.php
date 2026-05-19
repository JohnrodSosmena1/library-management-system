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


        DB::unprepared('DROP TRIGGER IF EXISTS after_borrowing_insert');
        DB::unprepared('DROP TRIGGER IF EXISTS after_borrowing_return_update');
        DB::unprepared('DROP TRIGGER IF EXISTS before_borrowing_prevent_unavailable');

        // 1) Validate quantity before insert
        DB::unprepared("
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

        // 2) Decrement quantity and update book status when borrowed
        DB::unprepared("
            CREATE TRIGGER after_borrowing_insert
            AFTER INSERT ON borrowings
            FOR EACH ROW
            BEGIN
                IF NEW.status = 'Borrowed' THEN
                    -- decrement inventory
                    UPDATE books
                    SET quantity = GREATEST(quantity - 1, 0)
                    WHERE id = NEW.book_id;

                    -- update status based on remaining quantity
                    UPDATE books
                    SET status = CASE
                        WHEN quantity > 0 THEN 'Available'
                        ELSE 'Borrowed'
                    END
                    WHERE id = NEW.book_id;
                END IF;
            END
        ");

        // 3) Update book status when returned
        DB::unprepared("\n            CREATE TRIGGER after_borrowing_return_update\n            AFTER UPDATE ON borrowings\n            FOR EACH ROW\n            BEGIN\n                IF NEW.status = 'Returned' AND OLD.status != 'Returned' THEN\n                    -- restore inventory\n                    UPDATE books\n                    SET quantity = quantity + 1\n                    WHERE id = NEW.book_id;\n\n                    -- update status based on remaining quantity\n                    UPDATE books\n                    SET status = CASE\n                        WHEN quantity > 0 THEN 'Available'\n                        ELSE 'Borrowed'\n                    END\n                    WHERE id = NEW.book_id;\n                END IF;\n            END\n        ");
    }

    public function down(): void
    {
        DB::unprepared('DROP TRIGGER IF EXISTS after_borrowing_insert');
        DB::unprepared('DROP TRIGGER IF EXISTS after_borrowing_return_update');
        DB::unprepared('DROP TRIGGER IF EXISTS before_borrowing_prevent_unavailable');
    }
};

