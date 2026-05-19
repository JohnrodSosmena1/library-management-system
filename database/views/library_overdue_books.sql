CREATE OR REPLACE VIEW overdue_books_list AS
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
    bk.title AS book_title,
    DATEDIFF(CURDATE(), b.due_date) AS days_late,
    DATEDIFF(CURDATE(), b.due_date) * 5.00 AS fine
FROM borrowings b
JOIN users u ON u.id = b.user_id
JOIN books bk ON bk.id = b.book_id
WHERE b.status = 'Overdue';

