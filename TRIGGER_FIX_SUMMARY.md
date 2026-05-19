# Book Quantity Bug Fix - Database Triggers

## Problem Identified

**Bug**: When an admin confirms a borrowing request, the book quantity was not decreasing. When the book was returned, the quantity increased by 2 instead of 1 (or returned to original state).

**Root Cause**: 
- Borrowings are created with status `Pending`
- When approved, the status is changed to `Borrowed` via an **UPDATE** operation
- The original `after_borrowing_insert` trigger only fires on **INSERT**, not UPDATE
- The original `after_borrowing_return_update` trigger only handles the Returned status
- **Result**: When approved (UPDATE Pending→Borrowed), no trigger fired, so quantity never decreased
- **Result**: When returned (UPDATE Borrowed→Returned), the quantity increased by 1, but the original decrease never happened, creating a net +1 instead of restoring the original quantity

## Solution Implemented

Updated the trigger migration file: `2026_05_09_000001_create_library_triggers_if_missing.php`

### Changes Made:

1. **Renamed trigger**: `after_borrowing_return_update` → `after_borrowing_status_update`
   
2. **New trigger logic** (`after_borrowing_status_update`):
   - Fires on UPDATE of borrowings table
   - **When status changes to 'Borrowed'** (approval case):
     - Decrements book quantity by 1
     - Updates book status based on remaining quantity
   - **When status changes to 'Returned'** (return case):
     - Increments book quantity by 1
     - Updates book status based on remaining quantity

### Trigger Flow Now:

```
CREATE BORROWING (Pending):
  No trigger fires ✓

APPROVE BORROWING (Pending → Borrowed):
  ✓ after_borrowing_status_update fires
  ✓ Quantity DECREASES by 1
  ✓ Book status updated

RETURN BORROWING (Borrowed → Returned):
  ✓ after_borrowing_status_update fires
  ✓ Quantity INCREASES by 1
  ✓ Book status updated
```

## Testing the Fix

### Scenario 1: Approve Borrowing
1. Book has quantity = 5
2. Admin approves a borrow request
3. **Expected**: quantity becomes 4 ✓
4. **Before fix**: quantity stayed at 5 ✗

### Scenario 2: Return Book
1. Book has quantity = 4 (after approval)
2. User returns the book
3. **Expected**: quantity becomes 5 ✓
4. **Before fix**: quantity became 6 ✗

## Files Modified

- `database/migrations/2026_05_09_000001_create_library_triggers_if_missing.php`
  - Updated trigger definitions
  - Replaced single UPDATE trigger with comprehensive status change handler
  
## Migration Executed

Migration successfully applied:
- Rolled back migrations to batch point
- Applied updated trigger migration with new `after_borrowing_status_update` trigger
- All 16 migrations now in "Ran" status
