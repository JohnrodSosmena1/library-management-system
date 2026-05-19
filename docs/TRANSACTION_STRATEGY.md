# Database Transaction & Trigger Strategy

## Implementation Summary

This document explains how the Library Management System handles database transactions and why database triggers are disabled.

---

## Problem Statement

The original system had a **hybrid approach**:
- Database triggers existed for inventory management
- Application code also modified quantities
- This created **race conditions** and **potential double-decrements**

### Risks Identified:
1. ❌ Concurrent approval requests could cause negative quantities
2. ❌ Triggers and application code could fire simultaneously
3. ❌ No explicit transaction rollback mechanism
4. ❌ Difficult to debug inventory inconsistencies

---

## Solution: Application-Managed Transactions

### Architecture Decision

✅ **Disable quantity triggers** (keep in DB for audit trail)  
✅ **Wrap all critical operations in DB::transaction()**  
✅ **Use pessimistic locking (lockForUpdate)**  
✅ **Provide explicit error handling**

---

## Implementation Details

### 1. Database Transactions

All critical borrowing operations are wrapped in explicit transactions:

```php
DB::transaction(function () {
    // 1. Lock resource
    $book = $book->lockForUpdate()->first();
    
    // 2. Validate
    if ($book->quantity <= 0) {
        throw new Exception('Out of stock');
    }
    
    // 3. Update atomically
    $book->decrement('quantity');
    $borrowing->update(['status' => 'Borrowed']);
    
    // 4. Auto-rollback on exception
});
```

### 2. Critical Operations Protected

#### ✅ `approveBorrowRequest()`
```
TRANSACTION:
  1. Lock book row (pessimistic)
  2. Validate quantity > 0
  3. Decrement quantity
  4. Update borrowing status → 'Borrowed'
  5. Update book status → 'Available' or 'Borrowed'
```

#### ✅ `processReturn()`
```
TRANSACTION:
  1. Lock book row
  2. Validate borrowing not already returned
  3. Calculate penalty
  4. Increment quantity
  5. Update borrowing status → 'Returned'
  6. Update book status → 'Available'
```

#### ✅ `rejectBorrowRequest()`
```
TRANSACTION:
  1. Validate pending status
  2. Update borrowing status → 'Rejected'
  (No inventory change needed)
```

#### ✅ `markOverdue()`
```
TRANSACTION:
  1. Find all borrowed items past due
  2. Update borrowing status → 'Overdue'
  3. Update book status → 'Overdue'
```

### 3. Race Condition Prevention

**Pessimistic Locking:**
```php
$book = $book->lockForUpdate()->first();
```

This prevents:
- Two librarians approving the same book simultaneously
- Double-decrementing quantities
- Inventory inconsistencies

**Example:**
```
User A approves → locks book → decrements qty=4
User B waits (blocked by lock)
User A commits → lock released
User B checks qty=4, validation passes
User B approves → locks book → decrements qty=3
```

---

## Why Triggers Are Disabled

### Triggers Still Exist In Database
The migrations still create triggers for reference:
- `after_borrowing_insert`
- `after_borrowing_return_update`
- `before_borrowing_prevent_unavailable`

**But they're NOT used** — see migration:
`2026_05_10_000001_disable_quantity_triggers.php`

### Why This Approach?

| Aspect | Triggers | Application | Winner |
|--------|----------|-------------|--------|
| **Concurrency Control** | ❌ No locking | ✅ Pessimistic lock | App |
| **Error Handling** | ⚠️ SIGNAL SQLSTATE | ✅ Exception & rollback | App |
| **Testability** | ❌ Hidden logic | ✅ Explicit code | App |
| **Maintenance** | ⚠️ Separate SQL | ✅ One code path | App |
| **Debugging** | ❌ Hard to trace | ✅ Application logs | App |

---

## Testing Transaction Safety

### Unit Tests Available

```bash
php artisan test --filter TransactionConcurrencyTest
```

Tests verify:
1. ✅ Concurrent approvals don't cause negative quantities
2. ✅ Returns maintain data consistency
3. ✅ Triggers don't interfere with application logic

---

## Operational Checklist

- ✅ All DB operations wrapped in `DB::transaction()`
- ✅ Critical sections use `lockForUpdate()`
- ✅ Exception handling with user-friendly messages
- ✅ Triggers documented and intentionally disabled
- ✅ Scheduler for overdue marking runs daily
- ✅ No manual SQL modifications (bypass transaction safety)

---

## Monitoring & Debugging

### Check Inventory Consistency

```sql
-- Query to find quantity inconsistencies
SELECT b.id, b.title, b.quantity, COUNT(borrow.id) as active_borrows
FROM books b
LEFT JOIN borrowings borrow ON b.id = borrow.book_id 
WHERE borrow.status IN ('Borrowed', 'Overdue')
GROUP BY b.id
HAVING active_borrows > b.quantity;
```

### Enable Query Logging

```php
// In .env or local config
DB_QUERY_LOGGING=true
```

### View Transaction Logs

```bash
tail -f storage/logs/laravel.log | grep "transaction"
```

---

## Future Considerations

### Option 1: Move to Event Sourcing
Track all inventory changes as events rather than direct updates.

### Option 2: Add Saga Pattern
Implement distributed transaction compensation for cross-service operations.

### Option 3: Database-Level Constraints
Add CHECK constraints at DB level for additional safety.

---

## References

- **Laravel Transactions**: https://laravel.com/docs/database#transactions
- **Pessimistic Locking**: https://laravel.com/docs/database#pessimistic-locking
- **Database Migrations**: `database/migrations/202x_*_*.php`
- **Controller**: `app/Http/Controllers/BorrowingController.php`
- **Model**: `app/Models/Borrowing.php`

---

**Last Updated:** May 18, 2026  
**Status:** ✅ Production Ready
