@extends('layouts.app')

@section('content')
<div class="container py-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1>📚 Books Library</h1>
        <a href="{{ route('books.create') }}" class="btn btn-primary">
            <i class="bi bi-plus-circle"></i> Add New Book
        </a>
    </div>

    <!-- Search & Filter Section -->
    <div class="card shadow-sm mb-4">
        <div class="card-body">
            <form method="GET" action="{{ route('books.index') }}" class="row g-3">
                <div class="col-md-4">
                    <input 
                        type="text" 
                        name="search" 
                        class="form-control" 
                        placeholder="Search by title, author, or ISBN..."
                        value="{{ request('search') }}"
                    >
                </div>
                <div class="col-md-3">
                    <select name="status" class="form-select">
                        <option value="">-- All Status --</option>
                        <option value="Available" {{ request('status') === 'Available' ? 'selected' : '' }}>Available</option>
                        <option value="Borrowed" {{ request('status') === 'Borrowed' ? 'selected' : '' }}>Borrowed</option>
                        <option value="Overdue" {{ request('status') === 'Overdue' ? 'selected' : '' }}>Overdue</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <select name="category" class="form-select">
                        <option value="">-- All Categories --</option>
                        @foreach($categories as $category)
                            <option value="{{ $category->id }}" {{ request('category') == $category->id ? 'selected' : '' }}>
                                {{ $category->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-success w-100">
                        <i class="bi bi-search"></i> Search
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Books Table -->
    @if($books->count() > 0)
        <div class="table-responsive">
            <table class="table table-hover table-striped">
                <thead class="table-dark">
                    <tr>
                        <th>Book ID</th>
                        <th>Title</th>
                        <th>Author</th>
                        <th>Category</th>
                        <th>ISBN</th>
                        <th>Qty</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($books as $book)
                        <tr>
                            <td><strong>{{ $book->formatted_id }}</strong></td>
                            <td>{{ $book->title }}</td>
                            <td>{{ $book->author }}</td>
                            <td>
                                <span class="badge bg-info">{{ $book->category->name ?? 'N/A' }}</span>
                            </td>
                            <td>{{ $book->isbn ?? 'N/A' }}</td>
                            <td>
                                <span class="badge bg-secondary">{{ $book->quantity }}</span>
                            </td>
                            <td>
                                @if($book->status === 'Available')
                                    <span class="badge bg-success">Available</span>
                                @elseif($book->status === 'Borrowed')
                                    <span class="badge bg-warning text-dark">Borrowed</span>
                                @else
                                    <span class="badge bg-danger">Overdue</span>
                                @endif
                            </td>
                            <td>
                                <a href="{{ route('books.edit', $book) }}" class="btn btn-sm btn-warning">
                                    <i class="bi bi-pencil"></i> Edit
                                </a>
                                <form action="{{ route('books.destroy', $book) }}" method="POST" style="display:inline;">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Delete this book?')">
                                        <i class="bi bi-trash"></i> Delete
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <nav class="mt-4">
            {{ $books->links('pagination::bootstrap-5') }}
        </nav>
    @else
        <div class="alert alert-info text-center">
            <i class="bi bi-info-circle"></i> No books found. <a href="{{ route('books.create') }}">Create one now.</a>
        </div>
    @endif
</div>
@endsection
