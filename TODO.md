# TODO - Borrowing Management Updates

## Task: Only show pending requests in Borrowing Management, after approve/reject go to Transactions

### Steps:

- [x]   1. Analyze existing code structure
- [x]   2. Update BorrowingController.php - change `borrowingIndex()` to only show Pending status
- [x]   3. Update BorrowingController.php - redirect to transactions.index after approve/reject
- [x]   4. Update borrowing/index.blade.php - remove filter tabs
- [x]   5. Update JavaScript for redirect after reject via AJAX
