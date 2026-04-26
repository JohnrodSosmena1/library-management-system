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

        // Mark book as borrowed and decrement quantity
        $book->update([
            'status'   => 'Borrowed',
            'quantity' => max(0, $book->quantity - 1),
        ]);

        return redirect()->route('transactions.index')
            ->with('success', "{$borrowing->formatted_id} created — \"{$book->title}\" borrowed successfully (Quantity: {$book->quantity}).");
    }

    // ── Return ───────────────────────────────────────────────

    public function returnForm(): View
    {
        $activeBorrowings = Borrowing::with(['user', 'book'])
            ->active()
            ->latest()
            ->get();

        return view('returnform.form', compact('activeBorrowings'));
    }

    public function processReturn(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'borrowing_id'    => 'required|exists:borrowings,id',
            'return_date'     => 'required|date',
            'book_condition'  => 'required|in:Good,Slightly damaged,Damaged',
        ]);

        $borrowing = Borrowing::with(['book'])->findOrFail($validated['borrowing_id']);

        if ($borrowing->status === Borrowing::STATUS_RETURNED) {
            return back()->with('error', 'This transaction has already been returned.');
        }

        $returnDate = Carbon::parse($validated['return_date']);
        $penalty    = $borrowing->computed_penalty;

        $borrowing->update([
            'return_date' => $returnDate,
            'status'      => Borrowing::STATUS_RETURNED,
            'penalty'     => $penalty,
        ]);

        // Mark book as available again and increment quantity
        $borrowing->book->update([
            'status'   => 'Available',
            'quantity' => $borrowing->book->quantity + 1,
        ]);

        $msg = $penalty > 0
            ? "\"{$borrowing->book->title}\" returned with a penalty of ₱{$penalty}."
            : "\"{$borrowing->book->title}\" returned successfully. No penalty.";

        return redirect()->route('transactions.index')->with('success', $msg);
    }

    // ── Overdue Sync (run via scheduler or artisan command) ──

    public function markOverdue(): void
    {
        Borrowing::where('status', Borrowing::STATUS_BORROWED)
            ->where('due_date', '<', now()->toDateString())
            ->each(function ($borrowing) {
                $borrowing->update(['status' => Borrowing::STATUS_OVERDUE]);
                $borrowing->book->update(['status' => 'Overdue'])   ;
            });
    }

    // ── User Borrow Request System ───────────────────────────

    /**
     * Show form to request a book
     */
    public function requestBorrowForm(): View
    {
        $user = \Auth::guard('user')->user();
        
        $books = Book::where('status', 'Available')
            ->orWhere('status', Borrowing::STATUS_BORROWED)
            ->with('category')
            ->orderBy('title')
            ->get();

        // Get user's pending requests to show edit modals
        $pendingBorrowings = $user->borrowings()
            ->with(['book'])
            ->where('status', Borrowing::STATUS_PENDING)
            ->get();

        return view('user.request-borrow', compact('books', 'pendingBorrowings'));
    }

    /**
     * Store a new borrow request
     */
    public function requestBorrow(Request $request): RedirectResponse
    {
        $user = \Auth::guard('user')->user();
        if (!$user) {
            return redirect('/login');
        }

        $validated = $request->validate([
            'book_id' => 'required|exists:books,id',
        ]);

        // Check if user already has a pending request for this book
        $existingRequest = Borrowing::where('user_id', $user->id)
            ->where('book_id', $validated['book_id'])
            ->where('status', Borrowing::STATUS_PENDING)
            ->first();

        if ($existingRequest) {
            return back()->with('error', 'You already have a pending request for this book.');
        }

        // Check eligibility (excluding pending requests)
        $active = $user->borrowings()
            ->whereIn('status', [Borrowing::STATUS_BORROWED, Borrowing::STATUS_OVERDUE])
            ->count();

        if ($user->hasOverdue()) {
            return back()->with('error', 'You have overdue books. Please return them before requesting new ones.');
        }

        if ($active >= Borrowing::BORROW_LIMIT) {
            return back()->with('error', "You have reached your borrowing limit ({$active}/" . Borrowing::BORROW_LIMIT . ").");
        }

        // Create pending request
        $book = Book::findOrFail($validated['book_id']);
        $borrowing = Borrowing::create([
            'user_id' => $user->id,
            'book_id' => $validated['book_id'],
            'status' => Borrowing::STATUS_PENDING,
        ]);

        return back()->with('success', "Request for \"{$book->title}\" submitted successfully. Awaiting librarian approval.");
    }

    /**
     * Update a pending borrow request (user can change book)
     */
    public function updateBorrowRequest(Request $request, Borrowing $borrowing): RedirectResponse
    {
        $user = \Auth::guard('user')->user();
        if (!$user || $borrowing->user_id !== $user->id) {
            return back()->with('error', 'Unauthorized.');
        }

        if ($borrowing->status !== Borrowing::STATUS_PENDING) {
            return back()->with('error', 'Can only edit pending requests.');
        }

        $validated = $request->validate([
            'book_id' => 'required|exists:books,id',
        ]);

        $borrowing->update([
            'book_id' => $validated['book_id'],
        ]);

        $book = $borrowing->book;
        return back()->with('success', "Request updated to \"{$book->title}\".");
    }

    /**
     * Delete a pending borrow request
     */
    public function deleteBorrowRequest(Borrowing $borrowing): RedirectResponse
    {
        $user = \Auth::guard('user')->user();
        if (!$user || $borrowing->user_id !== $user->id) {
            return back()->with('error', 'Unauthorized.');
        }

        if ($borrowing->status !== Borrowing::STATUS_PENDING) {
            return back()->with('error', 'Can only delete pending requests.');
        }

        $bookTitle = $borrowing->book->title;
        $borrowing->delete();

        return back()->with('success', "Request for \"{$bookTitle}\" cancelled.");
    }

    /**
     * Show pending borrow requests (librarian view)
     */
    public function pendingRequests(Request $request): View
    {
        $query = Borrowing::with(['user', 'book'])
            ->where('status', Borrowing::STATUS_PENDING)
            ->orderBy('created_at', 'desc');

        if ($request->filled('search')) {
            $search = $request->search;
            $query->whereHas('user', fn($q) => $q->where('name', 'like', "%{$search}%"))
                  ->orWhereHas('book', fn($q) => $q->where('title', 'like', "%{$search}%"));
        }

        $pendingRequests = $query->paginate(15)->withQueryString();

        return view('borrowform.pending-requests', compact('pendingRequests'));
    }

    /**
     * Librarian approves a borrow request
     */
    public function approveBorrowRequest(Request $request, Borrowing $borrowing): RedirectResponse
    {
        if ($borrowing->status !== Borrowing::STATUS_PENDING) {
            return back()->with('error', 'Only pending requests can be approved.');
        }

        $validated = $request->validate([
            'date_borrowed' => 'required|date',
            'librarian_id' => 'required|exists:librarians,id',
        ]);

        $book = $borrowing->book;

        // Check book availability
        if ($book->quantity <= 0) {
            return back()->with('error', "Book \"{$book->title}\" is out of stock.");
        }

        $dateBorrowed = Carbon::parse($validated['date_borrowed']);
        $dueDate = $dateBorrowed->copy()->addDays(Borrowing::LOAN_DAYS);

        $borrowing->update([
            'librarian_id' => $validated['librarian_id'],
            'date_borrowed' => $dateBorrowed,
            'due_date' => $dueDate,
            'status' => Borrowing::STATUS_BORROWED,
        ]);

        // Decrement book quantity
        $book->update([
            'status' => $book->quantity - 1 > 0 ? 'Available' : 'Borrowed',
            'quantity' => max(0, $book->quantity - 1),
        ]);

        return back()->with('success', "Request approved — \"{$book->title}\" borrowed by {$borrowing->user->name}.");
    }

    /**
     * Librarian rejects a borrow request
     */
    public function rejectBorrowRequest(Borrowing $borrowing): RedirectResponse
    {
        if ($borrowing->status !== Borrowing::STATUS_PENDING) {
            return back()->with('error', 'Only pending requests can be rejected.');
        }

        $bookTitle = $borrowing->book->title;
        $userName = $borrowing->user->name;

        $borrowing->update(['status' => Borrowing::STATUS_REJECTED]);

        return back()->with('success', "Request rejected — {$userName}'s request for \"{$bookTitle}\" has been rejected.");
    }
}