<?php

namespace App\Http\Controllers;

use App\Models\Book;
use App\Models\Borrowing;
use App\Models\User;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(): View
    {
        $totalBooks     = Book::sum('quantity');
        $uniqueTitles   = Book::count();
        $borrowed       = Borrowing::where('status', Borrowing::STATUS_BORROWED)->count();
        $overdue        = Borrowing::where('status', Borrowing::STATUS_OVERDUE)->count();
        $totalUsers     = User::count();
        $pendingRequests = Borrowing::where('status', Borrowing::STATUS_PENDING)->count();

        $recentActivity = Borrowing::with(['user', 'book'])
            ->latest()
            ->take(8)
            ->get();

        $overdueList = Borrowing::with(['user', 'book'])
            ->overdue()
            ->get();

        $recentPendingRequests = Borrowing::with(['user', 'book'])
            ->where('status', Borrowing::STATUS_PENDING)
            ->latest()
            ->take(5)
            ->get();

        return view('dasboard', compact(
            'totalBooks',
            'uniqueTitles',
            'borrowed',
            'overdue',
            'totalUsers',
            'pendingRequests',
            'recentActivity',
            'overdueList',
            'recentPendingRequests'
        ));
    }
}