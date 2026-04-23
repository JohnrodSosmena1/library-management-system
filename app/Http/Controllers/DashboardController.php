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
        $borrowed       = Borrowing::where('status', 'Borrowed')->count();
        $overdue        = Borrowing::where('status', 'Overdue')->count();
        $totalUsers     = User::count();

        $recentActivity = Borrowing::with(['user', 'book'])
            ->latest()
            ->take(8)
            ->get();

        $overdueList = Borrowing::with(['user', 'book'])
            ->overdue()
            ->get();

        return view('dasboard', compact(
            'totalBooks',
            'uniqueTitles',
            'borrowed',
            'overdue',
            'totalUsers',
            'recentActivity',
            'overdueList'
        ));
    }
}