<?php

use App\Http\Controllers\BookController;
use App\Http\Controllers\BorrowingController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Davao City Library — LMS Routes
|--------------------------------------------------------------------------
*/

// Dashboard
Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

// Books (CRUD)
Route::resource('books', BookController::class)->except(['show']);

// Categories
Route::get('/categories', [CategoryController::class, 'index'])->name('categories.index');
Route::post('/categories', [CategoryController::class, 'store'])->name('categories.store');
Route::delete('/categories/{category}', [CategoryController::class, 'destroy'])->name('categories.destroy');

// Users (CRUD)
Route::resource('users', UserController::class)->except(['show']);

// Borrowing — Borrow a book
Route::get('/borrow', [BorrowingController::class, 'borrowForm'])->name('borrow.form');
Route::post('/borrow', [BorrowingController::class, 'store'])->name('borrow.store');
Route::post('/borrow/check-eligibility', [BorrowingController::class, 'checkEligibility'])->name('borrow.eligibility');

// Return a book
Route::get('/return', [BorrowingController::class, 'returnForm'])->name('return.form');
Route::post('/return', [BorrowingController::class, 'processReturn'])->name('return.process');

// All transactions
Route::get('/transactions', [BorrowingController::class, 'index'])->name('transactions.index');