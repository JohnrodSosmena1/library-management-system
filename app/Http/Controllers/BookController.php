<?php

namespace App\Http\Controllers;

use App\Models\Book;
use App\Models\Category;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class BookController extends Controller
{
    public function index(Request $request): View
    {
        $query = Book::with('category');

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('author', 'like', "%{$search}%")
                  ->orWhere('isbn', 'like', "%{$search}%");
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('category')) {
            $query->where('category_id', $request->category);
        }

        $books      = $query->latest()->paginate(10)->withQueryString();
        $categories = Category::orderBy('name')->get();

        return view('books.index', compact('books', 'categories'));
    }

    public function create(): View
    {
        $categories = Category::orderBy('name')->get();
        return view('books.create', compact('categories'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'title'       => 'required|string|max:255',
            'author'      => 'required|string|max:255',
            'category_id' => 'required|exists:categories,id',
            'isbn'        => 'nullable|string|max:30|unique:books,isbn',
            'quantity'    => 'required|integer|min:1',
            'description' => 'nullable|string',
        ]);

        Book::create($validated);

        return redirect()->route('books.index')
            ->with('success', "Book \"{$validated['title']}\" added to catalog.");
    }

    public function edit(Book $book): View
    {
        $categories = Category::orderBy('name')->get();
        return view('books.edit', compact('book', 'categories'));
    }

    public function update(Request $request, Book $book): RedirectResponse
    {
        $validated = $request->validate([
            'title'       => 'required|string|max:255',
            'author'      => 'required|string|max:255',
            'category_id' => 'required|exists:categories,id',
            'isbn'        => "nullable|string|max:30|unique:books,isbn,{$book->id}",
            'quantity'    => 'required|integer|min:0',
            'status'      => 'required|in:Available,Borrowed,Overdue',
            'description' => 'nullable|string',
        ]);

        $book->update($validated);

        return redirect()->route('books.index')
            ->with('success', "Book \"{$book->title}\" updated successfully.");
    }

    public function destroy(Request $request, Book $book): RedirectResponse
    {
        $request->validate([
            'reason' => 'required|string',
        ]);

        // Prevent deletion if book has active borrowings
        if ($book->borrowings()->active()->exists()) {
            return back()->with('error', "Cannot delete \"{$book->title}\" — it has active borrowings.");
        }

        $title = $book->title;
        $book->delete();

        return redirect()->route('books.index')
            ->with('success', "Book \"{$title}\" deleted from inventory.");
    }
}