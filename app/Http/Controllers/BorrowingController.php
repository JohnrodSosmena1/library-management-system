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
use Illuminate\Support\Facades\DB;

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

    public function rejectFromManagement(Request $request, Borrowing $borrowing): JsonResponse
    {
        if ($borrowing->status !== Borrowing::STATUS_PENDING) {
            return response()->json(['error' => 'Only pending requests can be rejected.'], 422);
        }

        $borrowing->update(['status' => Borrowing::STATUS_REJECTED]);

        return response()->json(['success' => "Borrowing request rejected."]);
    }

    // ── Borrowed Books List ───────────────────────────────────────────────

    public function borrowedBooksIndex(Request $request): View
    {
        $query = Borrowing::with(['user', 'book', 'librarian'])
            ->where('status', Borrowing::STATUS_BORROWED);

        if ($request->filled('search')) {
            $search = $request->search;
            $query->whereHas('user', fn($q) => $q->whereRaw("concat_ws(' ', first_name, last_name) like ?", ["%{$search}%"]))
                ->orWhereHas('book', fn($q) => $q->where('title', 'like', "%{$search}%"));
        }

        $borrowed = $query->latest()->paginate(10)->withQueryString();

        return view('borrowed-books.index', compact('borrowed'));
    }

    // ── Overdue Books List ───────────────────────────────────────────────

    public function overdueBooksIndex(Request $request): View
    {
        $query = Borrowing::with(['user', 'book', 'librarian'])
            ->where('status', Borrowing::STATUS_OVERDUE);

        if ($request->filled('search')) {
            $search = $request->search;
            $query->whereHas('user', fn($q) => $q->whereRaw("concat_ws(' ', first_name, last_name) like ?", ["%{$search}%"]))
                ->orWhereHas('book', fn($q) => $q->where('title', 'like', "%{$search}%"));
        }

        $overdue = $query->latest()->paginate(10)->withQueryString();

        return view('overdue-books.index', compact('overdue'));
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

        try {
            $result = DB::transaction(function () use ($validated) {
                $eligibility = Borrowing::checkEligibility($validated['user_id']);
                if (!$eligibility['eligible']) {
                    throw new \Exception($eligibility['message']);
                }

                $dateBorrowed = Carbon::parse($validated['date_borrowed']);
                $dueDate      = $dateBorrowed->copy()->addDays(Borrowing::LOAN_DAYS);

                // Create borrowing record (stays pending until librarian approval)
                $borrowing = Borrowing::create([
                    'user_id'        => $validated['user_id'],
                    'book_id'        => $validated['book_id'],
                    'librarian_id'   => $validated['librarian_id'],
                    'date_borrowed'  => $dateBorrowed,
                    'due_date'       => $dueDate,
                    'status'         => Borrowing::STATUS_PENDING,
                ]);


                return $borrowing;
            });

            $book = $result->book;

            return redirect()->route('transactions.index')
                ->with('success', "{$result->formatted_id} created — \"{$book->title}\" borrowed successfully.");
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage())->withInput();
        }

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

        try {
            $msg = DB::transaction(function () use ($validated) {
                $borrowing = Borrowing::with(['book'])->findOrFail($validated['borrowing_id']);

                if ($borrowing->status === Borrowing::STATUS_RETURNED) {
                    throw new \Exception('This transaction has already been returned.');
                }

                $returnDate = Carbon::parse($validated['return_date']);
                $penalty    = $borrowing->computed_penalty;
                
                $book = Book::lockForUpdate()->find($borrowing->book_id);

                $borrowing->update([
                    'return_date' => $returnDate,
                    'status'      => Borrowing::STATUS_RETURNED,
                    'penalty'     => $penalty,
                ]);

                //triggers.
                $book->status = 'Available';
                $book->save();


                return $penalty > 0
                    ? "\"{$book->title}\" returned with a penalty of ₱{$penalty}."
                    : "\"{$book->title}\" returned successfully. No penalty.";
            });

            return redirect()->route('transactions.index')->with('success', $msg);
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage())->withInput();
        }
    }

    public function markOverdue(): void
    {
        DB::transaction(function () {
            Borrowing::where('status', Borrowing::STATUS_BORROWED)
                ->where('due_date', '<', now()->toDateString())
                ->each(function ($borrowing) {
                    $borrowing->update(['status' => Borrowing::STATUS_OVERDUE]);
                    $borrowing->book->update(['status' => 'Overdue']);
                });
        });
    }

    public function requestBorrowForm(): View
    {
        $user = \Auth::guard('user')->user();
        
        $books = Book::where('status', 'Available')
            ->orWhere('status', Borrowing::STATUS_BORROWED)
            ->with('category')
            ->orderBy('title')
            ->get();

        $pendingBorrowings = $user->borrowings()
            ->with(['book'])
            ->where('status', Borrowing::STATUS_PENDING)
            ->get();

        return view('user.request-borrow', compact('books', 'pendingBorrowings'));
    }

    public function requestBorrow(Request $request): RedirectResponse
    {
        $user = \Auth::guard('user')->user();
        if (!$user) {
            return redirect('/login');
        }

        $validated = $request->validate([
            'book_id' => 'required|exists:books,id',
        ]);

        $existingRequest = Borrowing::where('user_id', $user->id)
            ->where('book_id', $validated['book_id'])
            ->where('status', Borrowing::STATUS_PENDING)
            ->first();

        if ($existingRequest) {
            return back()->with('error', 'You already have a pending request for this book.');
        }

        $active = $user->borrowings()
            ->whereIn('status', [Borrowing::STATUS_BORROWED, Borrowing::STATUS_OVERDUE])
            ->count();

        if ($user->hasOverdue()) {
            return back()->with('error', 'You have overdue books. Please return them before requesting new ones.');
        }

        if ($active >= Borrowing::BORROW_LIMIT) {
            return back()->with('error', "You have reached your borrowing limit ({$active}/" . Borrowing::BORROW_LIMIT . ").");
        }

        $book = Book::findOrFail($validated['book_id']);
        $borrowing = Borrowing::create([
            'user_id' => $user->id,
            'book_id' => $validated['book_id'],
            'status' => Borrowing::STATUS_PENDING,
        ]);



        return back()->with('success', "Request for \"{$book->title}\" submitted successfully. Awaiting librarian approval.");
    }


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

        try {
   
            $result = DB::transaction(function () use ($borrowing, $validated) {
                // Lock book for update to prevent race conditions
                $book = Book::lockForUpdate()->find($borrowing->book_id);

                if ($book->quantity <= 0) {
                    throw new \Exception("Book \"{$book->title}\" is out of stock.");
                }

                $dateBorrowed = Carbon::parse($validated['date_borrowed']);
                $dueDate = $dateBorrowed->copy()->addDays(Borrowing::LOAN_DAYS);

                $borrowing->update([
                    'librarian_id' => $validated['librarian_id'],
                    'date_borrowed' => $dateBorrowed,
                    'due_date' => $dueDate,
                    'status' => Borrowing::STATUS_BORROWED,
                ]);

                $book->decrement('quantity');

                $book->status = $book->quantity > 0 ? 'Available' : 'Borrowed';
                $book->save();

                return $book;
            });

            return redirect()->route('transactions.index')
                ->with('success', "Request approved — \"{$result->title}\" borrowed by {$borrowing->user->fullName}.");
        } catch (\Exception $e) {
            $message = $e->getMessage();
            return $request->expectsJson()
                ? response()->json(['error' => $message], 422)
                : back()->with('error', $message)->withInput();
        }
    }

    public function rejectBorrowRequest(Request $request, Borrowing $borrowing)
    {
        if ($borrowing->status !== Borrowing::STATUS_PENDING) {
            $message = 'Only pending requests can be rejected.';
            return $request->expectsJson() 
                ? response()->json(['error' => $message], 422) 
                : back()->with('error', $message);
        }

        try {
            $result = DB::transaction(function () use ($borrowing) {
                $bookTitle = $borrowing->book->title;
                
                $borrowing->update(['status' => Borrowing::STATUS_REJECTED]);

                return $bookTitle;
            });

            return redirect()->route('transactions.index')
                ->with('success', "Request rejected — \"{$result}\" returned to inventory.");
        } catch (\Exception $e) {
            $message = 'Error rejecting request. Please try again.';
            return $request->expectsJson()
                ? response()->json(['error' => $message], 422)
                : back()->with('error', $message);
        }
    }
}

