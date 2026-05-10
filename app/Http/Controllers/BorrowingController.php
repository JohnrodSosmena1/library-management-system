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
            $query->whereHas('user', fn($q) => $q->whereRaw("concat_ws(' ', first_name, last_name) like ?", ["%{$search}%"]))
                  ->orWhereHas('book', fn($q) => $q->where('title', 'like', "%{$search}%"));
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $transactions = $query->latest()->paginate(10)->withQueryString();

        return view('transactions.index', compact('transactions'));
    }

// ── Borrowing Management (Pending Requests Only) ───────────────────

    public function borrowingIndex(Request $request): View
    {
        // Only show PENDING requests in Borrowing Management
        $query = Borrowing::with(['user', 'book', 'librarian'])
            ->where('status', Borrowing::STATUS_PENDING);

        if ($request->filled('search')) {
            $search = $request->search;
            $query->whereHas('user', fn($q) => $q->whereRaw("concat_ws(' ', first_name, last_name) like ?", ["%{$search}%"]))
                  ->orWhereHas('book', fn($q) => $q->where('title', 'like', "%{$search}%"));
        }

        $borrowings = $query->latest()->paginate(15)->withQueryString();

        return view('borrowing.index', compact('borrowings'));
    }

    /**
     * Update borrowing details (edit)
     */
    public function updateBorrowing(Request $request, Borrowing $borrowing): RedirectResponse
    {
        if (!in_array($borrowing->status, [Borrowing::STATUS_BORROWED, Borrowing::STATUS_OVERDUE])) {
            return back()->with('error', 'Only borrowed or overdue items can be edited.');
        }

        $validated = $request->validate([
            'due_date' => 'required|date',
            'librarian_id' => 'required|exists:librarians,id',
        ]);

        $borrowing->update([
            'due_date' => $validated['due_date'],
            'librarian_id' => $validated['librarian_id'],
        ]);

        return back()->with('success', 'Borrowing updated successfully.');
    }

    /**
     * Reject borrowing from management page
     */
    public function rejectFromManagement(Request $request, Borrowing $borrowing): JsonResponse
    {
        if ($borrowing->status !== Borrowing::STATUS_PENDING) {
            return response()->json(['error' => 'Only pending requests can be rejected.'], 422);
        }

        $borrowing->update(['status' => Borrowing::STATUS_REJECTED]);

        return response()->json(['success' => "Borrowing request rejected."]);
    }

    // ── Borrow ───────────────────────────────────────────────

    public function borrowForm(): View
    {
        $users      = User::where('status', 'Active')->orderBy('first_name')->get();
        $books      = Book::where('status', 'Available')->with('category')->orderBy('title')->get();
        $librarians = Librarian::orderBy('first_name')->get();

        return view('borrowform.form', compact('users', 'books', 'librarians'));
    }

    public function checkEligibility(Request $request): JsonResponse
    {
        $request->validate(['user_id' => 'required|exists:users,id']);
        $result = Borrowing::checkEligibility($request->user_id);
        return response()->json($result);
    }

    public function getUserRequestedBooks(Request $request): JsonResponse
    {
        $request->validate(['user_id' => 'required|exists:users,id']);
        
        $pendingRequests = Borrowing::where('user_id', $request->user_id)
            ->where('status', Borrowing::STATUS_PENDING)
            ->with(['book'])
            ->get();

        $books = $pendingRequests->map(function ($borrowing) {
            return [
                'id' => $borrowing->book->id,
                'title' => $borrowing->book->title,
                'author' => $borrowing->book->author,
                'formatted_id' => $borrowing->book->formatted_id,
            ];
        })->unique('id')->values();

        return response()->json([
            'books' => $books,
            'has_requests' => $books->count() > 0,
        ]);
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

        // Book availability checked during approval\n        $dateBorrowed = Carbon::parse($validated['date_borrowed']);\n        $dueDate      = $dateBorrowed->copy()->addDays(Borrowing::LOAN_DAYS);

        $borrowing = Borrowing::create([
            'user_id'        => $validated['user_id'],
            'book_id'        => $validated['book_id'],
            'librarian_id'   => $validated['librarian_id'],
            'date_borrowed'  => $dateBorrowed,
            'due_date'       => $dueDate,
            'status'         => Borrowing::STATUS_PENDING,
        ]);

        // NOTE: We do NOT change books.quantity here.
        // Quantity updates are handled by database triggers when status changes to Borrowed/Returned.

        $book = $borrowing->book;

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

        $book = $borrowing->book;

        // Count how many copies this user returned for this book in the system.
        // (In this app, typically each return action updates one borrowing row.)
        $restoreQty = Borrowing::where('id', $borrowing->id)
            ->where('book_id', $book->id)
            ->whereIn('status', [Borrowing::STATUS_BORROWED, Borrowing::STATUS_OVERDUE])
            ->count();

        $borrowing->update([
            'return_date' => $returnDate,
            'status'      => Borrowing::STATUS_RETURNED,
            'penalty'     => $penalty,
        ]);

        // Explicitly restore inventory (quantity triggers can be duplicated)
        if ($restoreQty > 0) {
            $book->increment('quantity', $restoreQty);
            $book->status = 'Available';
            $book->save();
        }

        $msg = $penalty > 0
            ? "\"{$book->title}\" returned with a penalty of ₱{$penalty}."
            : "\"{$book->title}\" returned successfully. No penalty.";

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
            // Keep request pending until librarian approval/rejection.
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
            $query->whereHas('user', fn($q) => $q->whereRaw("concat_ws(' ', first_name, last_name) like ?", ["%{$search}%"]))
                  ->orWhereHas('book', fn($q) => $q->where('title', 'like', "%{$search}%"));
        }

        $pendingRequests = $query->paginate(15)->withQueryString();

        return view('borrowform.pending-requests', compact('pendingRequests'));
    }

    /**
     * Librarian approves a borrow request
     */
    public function approveBorrowRequest(Request $request, Borrowing $borrowing)
    {
        if ($borrowing->status !== Borrowing::STATUS_PENDING) {
            $message = 'Only pending requests can be approved.';
            return $request->expectsJson() 
                ? response()->json(['error' => $message], 422) 
                : back()->with('error', $message);
        }

        $validated = $request->validate([
            'date_borrowed' => 'required|date',
            'librarian_id' => 'required|exists:librarians,id',
        ]);

        $book = $borrowing->book;

        // Check book availability
        if ($book->quantity <= 0) {
            $message = "Book \"{$book->title}\" is out of stock.";
            return $request->expectsJson() 
                ? response()->json(['error' => $message], 422) 
                : back()->with('error', $message);
        }

        $dateBorrowed = Carbon::parse($validated['date_borrowed']);
        $dueDate = $dateBorrowed->copy()->addDays(Borrowing::LOAN_DAYS);

        $borrowing->update([
            'librarian_id' => $validated['librarian_id'],
            'date_borrowed' => $dateBorrowed,
            'due_date' => $dueDate,
            'status' => Borrowing::STATUS_BORROWED,
        ]);

        // Update inventory explicitly: this request borrows exactly 1 row (1 copy)
        // but we still lock the row to prevent race conditions.
        $book = $borrowing->book()->lockForUpdate()->first();
        if ($book->quantity <= 0) {
            $message = "Book \"{$book->title}\" is out of stock.";
            return $request->expectsJson()
                ? response()->json(['error' => $message], 422)
                : back()->with('error', $message);
        }

        $book->decrement('quantity');
        $book->status = $book->quantity > 0 ? 'Available' : 'Borrowed';
        $book->save();

        // Redirect to Transactions after approval
        return redirect()->route('transactions.index')
            ->with('success', "Request approved — \"{$book->title}\" borrowed by {$borrowing->user->fullName}.");
    }

    /**
     * Librarian rejects a borrow request
     */
    public function rejectBorrowRequest(Request $request, Borrowing $borrowing)
    {
        if ($borrowing->status !== Borrowing::STATUS_PENDING) {
            $message = 'Only pending requests can be rejected.';
            return $request->expectsJson() 
                ? response()->json(['error' => $message], 422) 
                : back()->with('error', $message);
        }

        $bookTitle = $borrowing->book->title;
        $userName = $borrowing->user->fullName();

        $borrowing->update(['status' => Borrowing::STATUS_REJECTED]);

        // Redirect to Transactions after rejection
        return redirect()->route('transactions.index')
            ->with('success', "Request rejected — \"{$bookTitle}\" returned to inventory.");
    }
}

