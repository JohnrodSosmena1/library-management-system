<?php

namespace App\Http\Controllers;

use App\Models\Book;
use App\Models\Borrowing;
use Illuminate\Support\Facades\Auth;

class UserDashboardController extends Controller
{
    /**
     * Display the user dashboard
     */
    public function index()
    {
        $user = Auth::guard('user')->user();

        if (!$user) {
            return redirect('/login');
        }

        // Get user's borrowing statistics
        $activeBorrows = $user->activeBorrowings()->count();
        $pendingRequests = $user->borrowings()->where('status', Borrowing::STATUS_PENDING)->count();
        $overdueBorrows = $user->borrowings()->where('status', Borrowing::STATUS_OVERDUE)->count();

        // Calculate total penalties
        $totalPenalties = $user->borrowings()
            ->where('status', Borrowing::STATUS_RETURNED)
            ->sum('penalty');

        // Get pending requests with book details
        $pendingBorrowings = $user->borrowings()
            ->with(['book'])
            ->where('status', Borrowing::STATUS_PENDING)
            ->orderBy('created_at', 'desc')
            ->get();

        // Get approved borrowings with details
        $approvedBorrowings = $user->borrowings()
            ->with(['book', 'librarian'])
            ->whereIn('status', [Borrowing::STATUS_BORROWED, Borrowing::STATUS_OVERDUE])
            ->orderBy('due_date', 'asc')
            ->get();

        // Get transaction history (returned & rejected)
        $transactionHistory = $user->borrowings()
            ->with(['book'])
            ->whereIn('status', [Borrowing::STATUS_RETURNED, Borrowing::STATUS_REJECTED, Borrowing::STATUS_OVERDUE])
            ->orderBy('created_at', 'desc')
            ->paginate(5);

        // Get available books for edit modal
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
}
