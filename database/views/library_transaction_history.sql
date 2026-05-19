CREATE OR REPLACE VIEW transaction_history_list AS
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
    CASE
        WHEN b.status = 'Overdue' THEN GREATEST(DATEDIFF(CURDATE(), b.due_date), 0)
        WHEN b.status = 'Returned' AND b.return_date IS NOT NULL THEN GREATEST(DATEDIFF(b.return_date, b.due_date), 0)
        ELSE 0
    END AS days_late,
    CASE
        WHEN b.status IN ('Overdue','Returned') THEN
            (CASE
                WHEN b.status = 'Overdue' THEN GREATEST(DATEDIFF(CURDATE(), b.due_date), 0)
                WHEN b.status = 'Returned' AND b.return_date IS NOT NULL THEN GREATEST(DATEDIFF(b.return_date, b.due_date), 0)
                ELSE 0
            END) * 5.00
        ELSE 0
    END AS fine
FROM borrowings b
JOIN users u ON u.id = b.user_id
JOIN books bk ON bk.id = b.book_id;

