<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\BookController;
use App\Http\Controllers\BorrowingController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\UserDashboardController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Davao City Library — LMS Routes
|--------------------------------------------------------------------------
*/

// Authentication Routes
Route::get('/login', [AuthController::class, 'loginForm'])->name('login');
Route::post('/login', [AuthController::class, 'login'])->name('login.store');
Route::get('/register', [AuthController::class, 'registerForm'])->name('register');
Route::post('/register', [AuthController::class, 'register'])->name('register.store');
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

// Public Home Page
Route::get('/', function () {
    if (\Auth::guard('user')->check()) {
        return redirect()->route('dashboard.user');
    } elseif (\Auth::guard('librarian')->check()) {
        return redirect()->route('dashboard');
    }
    return redirect()->route('login');
})->name('home');

// User Dashboard (protected by auth:user middleware)
Route::middleware('auth:user')->group(function () {
    Route::get('/dashboard', [UserDashboardController::class, 'index'])->name('dashboard.user');
    
    // User Borrow Request System
    Route::get('/request-borrow', [BorrowingController::class, 'requestBorrowForm'])->name('borrow.request');
    Route::post('/request-borrow', [BorrowingController::class, 'requestBorrow'])->name('borrow.request.store');
    Route::put('/borrow/{borrowing}', [BorrowingController::class, 'updateBorrowRequest'])->name('borrow.update');
    Route::delete('/borrow/{borrowing}', [BorrowingController::class, 'deleteBorrowRequest'])->name('borrow.delete');
    
    // User Profile
    Route::get('/profile', [UserController::class, 'userProfile'])->name('user.profile');
    Route::put('/profile', [UserController::class, 'updateProfile'])->name('user.profile.update');
});

// Librarian Routes (protected by auth:librarian middleware)
Route::middleware('auth:librarian')->group(function () {
    // Librarian Dashboard
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

// Borrowing Management (Combined View - replaces Pending Requests & Borrow Book)
    Route::get('/borrowing', [BorrowingController::class, 'borrowingIndex'])->name('borrowing.index');
    Route::put('/borrowing/{borrowing}/approve', [BorrowingController::class, 'approveBorrowRequest'])->name('borrowing.approve');
    Route::put('/borrowing/{borrowing}/reject', [BorrowingController::class, 'rejectFromManagement'])->name('borrowing.reject');
    Route::put('/borrowing/{borrowing}', [BorrowingController::class, 'updateBorrowing'])->name('borrowing.update');
    Route::delete('/borrowing/{borrowing}', [BorrowingController::class, 'destroyBorrowing'])->name('borrowing.destroy');

    // Books (CRUD)
    Route::resource('books', BookController::class)->except(['show']);

    // Categories
    Route::get('/categories', [CategoryController::class, 'index'])->name('categories.index');
    Route::post('/categories', [CategoryController::class, 'store'])->name('categories.store');
    Route::delete('/categories/{category}', [CategoryController::class, 'destroy'])->name('categories.destroy');

    // Users (CRUD)
    Route::resource('users', UserController::class)->except(['show']);

    // Return a book
    Route::get('/return', [BorrowingController::class, 'returnForm'])->name('return.form');
    Route::post('/return', [BorrowingController::class, 'processReturn'])->name('return.process');

    // All transactions
    Route::get('/transactions', [BorrowingController::class, 'index'])->name('transactions.index');
});
