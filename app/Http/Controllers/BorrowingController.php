<?php

namespace App\Http\Controllers;

use App\Models\Book;
use App\Models\Borrowing;
use App\Models\Librarian;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class BorrowingController extends Controller
{
    // ── Transactions List ────────────────────────────────────

    public function index(Request $request): View
    {
        $query = Borrowing::with(['user', 'book', 'librarian']);

        if ($request->filled('search')) {
            $search = $request->search;
            $query->whereHas('user', fn($q) => $q->where('name', 'like', "%{$search}%"))
                  ->orWhereHas('book', fn($q) => $q->where('title', 'like', "%{$search}%"));
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $transactions = $query->latest()->paginate(10)->withQueryString();

        return view('transactions.index', compact('transactions'));
    }

    // ── Borrow ───────────────────────────────────────────────

    public function borrowForm(): View
    {
        $users      = User::where('status', 'Active')->orderBy('name')->get();
        $books      = Book::where('status', 'Available')->with('category')->orderBy('title')->get();
        $librarians = Librarian::orderBy('name')->get();

        return view('borrowform.form', compact('users', 'books', 'librarians'));
    }

    public function checkEligibility(Request $request): JsonResponse
    {
        $request->validate(['user_id' => 'required|exists:users,id']);
        $result = Borrowing::checkEligibility($request->user_id);
        return response()->json($result);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'user_id'      => 'required|exists:users,id',
            'book_id'      => 'required|exists:books,id',
            'librarian_id' => 'required|exists:librarians,id',
            'date_borrowed'=> 'required|date',
        ]);

        // Re-check eligibility server-side
        $eligibility = Borrowing::checkEligibility($validated['user_id']);
        if (!$eligibility['eligible']) {
            return back()->with('error', $eligibility['message'])->withInput();
        }

        $book = Book::findOrFail($validated['book_id']);
        if (!$book->isAvailable()) {
            return back()->with('error', "Book \"{$book->title}\" is not available.")->withInput();
        }

        $dateBorrowed = Carbon::parse($validated['date_borrowed']);
        $dueDate      = $dateBorrowed->copy()->addDays(Borrowing::LOAN_DAYS);

        $borrowing = Borrowing::create([
            'user_id'       => $validated['user_id'],
            'book_id'       => $validated['book_id'],
            'librarian_id'  => $validated['librarian_id'],
            'date_borrowed' => $dateBorrowed,
            'due_date'      => $dueDate,
            'status'        => 'Borrowed',
        ]);

        // Mark book as borrowed
        $book->update(['status' => 'Borrowed']);

        return redirect()->route('transactions.index')
            ->with('success', "{$borrowing->formatted_id} created — \"{$book->title}\" borrowed successfully.");
    }

    // ── Return ───────────────────────────────────────────────

    public function returnForm(): View
    {
        $activeBorrowings = Borrowing::with(['user', 'book'])
            ->active()
            ->latest()
            ->get();

        return view('return.form', compact('activeBorrowings'));
    }

    public function processReturn(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'borrowing_id'    => 'required|exists:borrowings,id',
            'return_date'     => 'required|date',
            'book_condition'  => 'required|in:Good,Slightly damaged,Damaged',
        ]);

        $borrowing = Borrowing::with(['book'])->findOrFail($validated['borrowing_id']);

        if ($borrowing->status === 'Returned') {
            return back()->with('error', 'This transaction has already been returned.');
        }

        $returnDate = Carbon::parse($validated['return_date']);
        $penalty    = $borrowing->computed_penalty;

        $borrowing->update([
            'return_date' => $returnDate,
            'status'      => 'Returned',
            'penalty'     => $penalty,
        ]);

        // Mark book as available again
        $borrowing->book->update(['status' => 'Available']);

        $msg = $penalty > 0
            ? "\"{$borrowing->book->title}\" returned with a penalty of ₱{$penalty}."
            : "\"{$borrowing->book->title}\" returned successfully. No penalty.";

        return redirect()->route('transactions.index')->with('success', $msg);
    }

    // ── Overdue Sync (run via scheduler or artisan command) ──

    public function markOverdue(): void
    {
        Borrowing::where('status', 'Borrowed')
            ->where('due_date', '<', now()->toDateString())
            ->each(function ($borrowing) {
                $borrowing->update(['status' => 'Overdue']);
                $borrowing->book->update(['status' => 'Overdue']);
            });
    }
}