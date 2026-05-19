<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\Borrowing;
use App\Models\Librarian;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TransactionConcurrencyTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test that concurrent approval requests don't cause negative quantities
     */
    public function test_concurrent_approvals_prevent_negative_quantity(): void
    {
        // Setup: 1 user, 1 librarian, 1 book with 1 copy
        $user = User::factory()->create(['status' => 'Active']);
        $librarian = Librarian::factory()->create();
        $book = Book::create([
            'title' => 'Test Book',
            'author' => 'Test Author',
            'category_id' => 1,
            'quantity' => 1,
            'status' => 'Available',
        ]);

        // Create 2 pending borrow requests for the same book
        $borrowing1 = Borrowing::create([
            'user_id' => $user->id,
            'book_id' => $book->id,
            'status' => Borrowing::STATUS_PENDING,
        ]);

        $borrowing2 = Borrowing::create([
            'user_id' => $user->id,
            'book_id' => $book->id,
            'status' => Borrowing::STATUS_PENDING,
        ]);

        // Simulate concurrent approvals (second one should fail due to lockForUpdate)
        $response1 = $this->post(route('borrowing.approve', $borrowing1), [
            'date_borrowed' => Carbon::now()->toDateString(),
            'librarian_id' => $librarian->id,
        ]);

        // Refresh book to get updated quantity
        $book->refresh();

        // Second approval should fail (book out of stock)
        $response2 = $this->post(route('borrowing.approve', $borrowing2), [
            'date_borrowed' => Carbon::now()->toDateString(),
            'librarian_id' => $librarian->id,
        ]);

        $response2->assertSessionHasErrors('error');

        // Verify quantity never went negative
        $book->refresh();
        $this->assertGreaterThanOrEqual(0, $book->quantity);
        $this->assertEquals(0, $book->quantity); // Should be 0 after first approval
    }

    /**
     * Test that return process with transactions maintains data consistency
     */
    public function test_return_process_maintains_consistency(): void
    {
        // Setup
        $user = User::factory()->create(['status' => 'Active']);
        $book = Book::create([
            'title' => 'Return Test',
            'author' => 'Test',
            'category_id' => 1,
            'quantity' => 0,
            'status' => 'Borrowed',
        ]);

        $borrowing = Borrowing::create([
            'user_id' => $user->id,
            'book_id' => $book->id,
            'date_borrowed' => Carbon::now()->subDays(10),
            'due_date' => Carbon::now()->subDays(5),
            'status' => Borrowing::STATUS_BORROWED,
        ]);

        // Process return
        $this->post(route('return.process'), [
            'borrowing_id' => $borrowing->id,
            'return_date' => Carbon::now()->toDateString(),
            'book_condition' => 'Good',
        ]);

        // Verify transaction updated both tables
        $borrowing->refresh();
        $book->refresh();

        $this->assertEquals(Borrowing::STATUS_RETURNED, $borrowing->status);
        $this->assertEquals(1, $book->quantity);
        $this->assertEquals('Available', $book->status);
    }

    /**
     * Verify that database triggers do NOT interfere with application transaction logic
     */
    public function test_triggers_disabled_for_quantity_management(): void
    {
        // This test documents that triggers exist but are intentionally not used
        // Check that quantity changes only via application code, not triggers

        $user = User::factory()->create(['status' => 'Active']);
        $librarian = Librarian::factory()->create();
        $book = Book::create([
            'title' => 'Trigger Test',
            'author' => 'Test',
            'category_id' => 1,
            'quantity' => 5,
            'status' => 'Available',
        ]);

        $initialQty = $book->quantity;

        // Create and approve borrowing
        $borrowing = Borrowing::create([
            'user_id' => $user->id,
            'book_id' => $book->id,
            'status' => Borrowing::STATUS_PENDING,
        ]);

        $this->post(route('borrowing.approve', $borrowing), [
            'date_borrowed' => Carbon::now()->toDateString(),
            'librarian_id' => $librarian->id,
        ]);

        $book->refresh();

        // Quantity should decrease by 1 (no double-decrement from trigger)
        $this->assertEquals($initialQty - 1, $book->quantity);
    }
}
