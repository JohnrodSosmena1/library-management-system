<?php

namespace App\Http\Controllers;

use App\Models\Book;
use App\Models\Borrowing;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class UserDashboardController extends Controller
{
    
    public function index()
    {
        $user = Auth::guard('user')->user();

        if (!$user) {
            return redirect('/login');
        }

        $activeBorrows = $user->activeBorrowings()->count();
        $pendingRequests = $user->borrowings()->where('status', Borrowing::STATUS_PENDING)->count();
        $overdueBorrows = $user->borrowings()->where('status', Borrowing::STATUS_OVERDUE)->count();

        $totalPenalties = $user->borrowings()
            ->where('status', Borrowing::STATUS_RETURNED)
            ->sum('penalty');

        $pendingBorrowings = $user->borrowings()
            ->with(['book'])
            ->where('status', Borrowing::STATUS_PENDING)
            ->orderBy('created_at', 'desc')
            ->get();

        $approvedBorrowings = $user->borrowings()
            ->with(['book', 'librarian'])
            ->whereIn('status', [Borrowing::STATUS_BORROWED, Borrowing::STATUS_OVERDUE])
            ->orderBy('due_date', 'asc')
            ->get();

        $transactionHistory = $user->borrowings()
            ->with(['book'])
            ->whereIn('status', [Borrowing::STATUS_RETURNED, Borrowing::STATUS_REJECTED, Borrowing::STATUS_OVERDUE])
            ->orderBy('created_at', 'desc')
            ->paginate(5);

        $availableBooks = Book::where('status', 'Available')
            ->orWhere('status', Borrowing::STATUS_BORROWED)
            ->with('category')
            ->orderBy('title')
            ->get();

        return view('user.dashboard', compact(
            'user',
            'activeBorrows',
            'pendingRequests',
            'overdueBorrows',
            'totalPenalties',
            'pendingBorrowings',
            'approvedBorrowings',
            'transactionHistory',
            'availableBooks'
        ));
    }

    
    public function userReturnForm()
    {
        $user = Auth::guard('user')->user();
        $activeBorrowings = $user->borrowings()
            ->with(['book'])
            ->active()
            ->latest()
            ->get();

        return view('user.return-form', compact('activeBorrowings'));
    }

   
    public function processUserReturn(Request $request)
    {
        $validated = $request->validate([
            'borrowing_id' => 'required|exists:borrowings,id',
            'return_date'  => 'required|date',
            'condition'    => 'required|in:Good,Slightly damaged,Damaged',
        ]);


        $borrowing = Borrowing::with('book')->findOrFail($validated['borrowing_id']);
        $user = Auth::guard('user')->user();

        // Authorization: must be user's borrowing
        if ($borrowing->user_id !== $user->id) {
            return back()->with('error', 'Unauthorized: This is not your borrowing.');
        }

        if ($borrowing->status === Borrowing::STATUS_RETURNED) {
            return back()->with('error', 'This book has already been returned.');
        }

        if (!in_array($borrowing->status, [Borrowing::STATUS_BORROWED, Borrowing::STATUS_OVERDUE])) {
            return back()->with('error', 'Can only return active (borrowed/overdue) books.');
        }

        $returnDate = Carbon::parse($validated['return_date']);
        $penalty = $borrowing->computed_penalty;

        $book = $borrowing->book()->lockForUpdate()->first();
        $restoreQty = Borrowing::where('id', $borrowing->id)
            ->where('book_id', $book->id)
            ->whereIn('status', [Borrowing::STATUS_BORROWED, Borrowing::STATUS_OVERDUE])
            ->count();

        $borrowing->update([
            'return_date' => $returnDate,
            'status'      => Borrowing::STATUS_RETURNED,
            'penalty'     => $penalty,
        ]);


        if ($restoreQty > 0) {
            $book->increment('quantity', $restoreQty);
            $book->status = 'Available';
            $book->save();
        }

        $msg = $penalty > 0
            ? "\"{$book->title}\" returned with penalty ₱" . number_format($penalty, 2) . "."
            : "\"{$book->title}\" returned on time. No penalty.";


        return redirect()->route('dashboard.user')->with('success', $msg);
    }
}
