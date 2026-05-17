-- MySQL View: borrowed books (status = 'Borrowed')
-- Usage in phpMyAdmin: use this SQL under your database

CREATE OR REPLACE VIEW borrowed_books_list AS
SELECT
    b.id AS borrowing_id,
    b.status,
    b.date_borrowed AS borrowed_date,
    b.due_date,
    b.return_date,
    CONCAT('TXN-', LPAD(b.id, 4, '0')) AS formatted_id,
    u.id AS user_id,
    CONCAT_WS(' ', u.first_name, u.last_name) AS user_name,
    bk.id AS book_id,
    bk.title AS book_title
FROM borrowings b
JOIN users u ON u.id = b.user_id
JOIN books bk ON bk.id = b.book_id
WHERE b.status = 'Borrowed';

