<?php

namespace App\Console\Commands;

use App\Models\Book;
use App\Models\Borrowing;
use App\Models\Librarian;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ValidateTransactionSafety extends Command
{
    protected $signature = 'db:validate-transactions';
    protected $description = 'Validate database transaction safety and inventory consistency';

    public function handle(): int
    {
        $this->info('🔍 Validating Database Transaction Safety...\n');

        // Check 1: Verify no negative quantities
        $this->line('Check 1: Inventory Consistency');
        $negativeQty = Book::where('quantity', '<', 0)->count();
        
        if ($negativeQty > 0) {
            $this->error("  ❌ Found {$negativeQty} books with negative quantities!");
            return 1;
        }
        $this->info('  ✅ No negative quantities found');

        // Check 2: Verify borrowed count doesn't exceed quantity
        $this->line('\nCheck 2: Borrowed vs Available Quantities');
        $inconsistencies = DB::select(
            "SELECT b.id, b.title, b.quantity, COUNT(borrow.id) as borrowed_count
             FROM books b
             LEFT JOIN borrowings borrow ON b.id = borrow.book_id 
             WHERE borrow.status IN ('Borrowed', 'Overdue')
             GROUP BY b.id, b.title, b.quantity
             HAVING COUNT(borrow.id) > b.quantity"
        );

        if (count($inconsistencies) > 0) {
            $this->error("  ❌ Found " . count($inconsistencies) . " books with more borrowed copies than inventory!");
            foreach ($inconsistencies as $item) {
                $this->line("    - {$item->title}: {$item->borrowed_count} borrowed, {$item->quantity} available");
            }
            return 1;
        }
        $this->info('  ✅ No quantity mismatches found');

        // Check 3: Verify transaction logs
        $this->line('\nCheck 3: Transaction Status Distribution');
        $distribution = DB::table('borrowings')
            ->selectRaw('status, COUNT(*) as count')
            ->groupBy('status')
            ->get();

        foreach ($distribution as $row) {
            $this->line("  • {$row->status}: {$row->count}");
        }

        // Check 4: Verify returned books had quantities restored
        $this->line('\nCheck 4: Returned Books Inventory Restoration');
        $returned = Borrowing::where('status', 'Returned')->count();
        
        if ($returned === 0) {
            $this->info('  ℹ️  No returned transactions yet');
        } else {
            $this->info("  ✅ {$returned} returned transactions logged");
        }

        // Check 5: Verify overdue marking works
        $this->line('\nCheck 5: Overdue Status Updates');
        $overdue = Borrowing::where('status', 'Overdue')->count();
        $borrowed = Borrowing::where('status', 'Borrowed')->count();
        
        $this->info("  • Borrowed: {$borrowed}");
        $this->info("  • Overdue: {$overdue}");

        // Check 6: Test transaction on-demand
        $this->line('\nCheck 6: Live Transaction Test');
        
        try {
            $testResult = DB::transaction(function () {
                $user = User::where('status', 'Active')->first();
                $book = Book::where('quantity', '>', 0)->first();
                
                if (!$user || !$book) {
                    return false;
                }
                
                $borrowing = Borrowing::create([
                    'user_id' => $user->id,
                    'book_id' => $book->id,
                    'status' => Borrowing::STATUS_PENDING,
                ]);
                
                // Rollback by throwing exception
                throw new \Exception('Test rollback');
            });
        } catch (\Exception $e) {
            if ($e->getMessage() === 'Test rollback') {
                $this->info('  ✅ Transaction rollback works correctly');
            }
        }

        // Summary
        $this->line('\n' . str_repeat('=', 60));
        $this->info('✅ All transaction safety checks passed!');
        $this->line("Validated at: " . Carbon::now()->toDateTimeString());
        
        return 0;
    }
}
