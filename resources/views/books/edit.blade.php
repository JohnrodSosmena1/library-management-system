@extends('layouts.app')

@section('content')
<div class="container py-5">
    <h1 class="mb-4">✏️ Edit Book</h1>

    <div class="row">
        <div class="col-md-8">
            <div class="card shadow-sm">
                <div class="card-body p-4">
                    <form action="{{ route('books.update', $book) }}" method="POST">
                        @csrf @method('PUT')

                        <div class="mb-3">
                            <label for="title" class="form-label">Book Title *</label>
                            <input 
                                type="text" 
                                class="form-control @error('title') is-invalid @enderror" 
                                id="title"
                                name="title"
                                value="{{ old('title', $book->title) }}"
                                required
                            >
                            @error('title')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="mb-3">
                            <label for="author" class="form-label">Author *</label>
                            <input 
                                type="text" 
                                class="form-control @error('author') is-invalid @enderror" 
                                id="author"
                                name="author"
                                value="{{ old('author', $book->author) }}"
                                required
                            >
                            @error('author')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="category_id" class="form-label">Category *</label>
                                <select 
                                    class="form-select @error('category_id') is-invalid @enderror" 
                                    id="category_id"
                                    name="category_id"
                                    required
                                >
                                    @foreach($categories as $category)
                                        <option value="{{ $category->id }}" {{ old('category_id', $book->category_id) == $category->id ? 'selected' : '' }}>
                                            {{ $category->name }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('category_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>

                            <div class="col-md-6 mb-3">
                                <label for="isbn" class="form-label">ISBN</label>
                                <input 
                                    type="text" 
                                    class="form-control @error('isbn') is-invalid @enderror" 
                                    id="isbn"
                                    name="isbn"
                                    value="{{ old('isbn', $book->isbn) }}"
                                >
                                @error('isbn')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="quantity" class="form-label">Quantity *</label>
                                <input 
                                    type="number" 
                                    class="form-control @error('quantity') is-invalid @enderror" 
                                    id="quantity"
                                    name="quantity"
                                    min="0"
                                    value="{{ old('quantity', $book->quantity) }}"
                                    required
                                >
                                @error('quantity')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>

                            <div class="col-md-6 mb-3">
                                <label for="status" class="form-label">Status *</label>
                                <select 
                                    class="form-select @error('status') is-invalid @enderror" 
                                    id="status"
                                    name="status"
                                    required
                                >
                                    <option value="Available" {{ old('status', $book->status) === 'Available' ? 'selected' : '' }}>Available</option>
                                    <option value="Borrowed" {{ old('status', $book->status) === 'Borrowed' ? 'selected' : '' }}>Borrowed</option>
                                    <option value="Overdue" {{ old('status', $book->status) === 'Overdue' ? 'selected' : '' }}>Overdue</option>
                                </select>
                                @error('status')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="description" class="form-label">Description</label>
                            <textarea 
                                class="form-control @error('description') is-invalid @enderror" 
                                id="description"
                                name="description"
                                rows="4"
                            >{{ old('description', $book->description) }}</textarea>
                            @error('description')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-check-circle"></i> Update Book
                            </button>
                            <a href="{{ route('books.index') }}" class="btn btn-secondary">
                                <i class="bi bi-x-circle"></i> Cancel
                            </a>
                        </div>
                    </form>

                    <!-- Delete Section -->
                    <hr class="my-4">
                    <h5 class="text-danger">⚠️ Danger Zone</h5>
                    <p class="small text-muted">Remove this book from inventory permanently.</p>
                    
                    @if(!$book->borrowings()->active()->exists())
                        <form action="{{ route('books.destroy', $book) }}" method="POST" class="mt-3">
                            @csrf @method('DELETE')
                            <div class="mb-3">
                                <label for="reason" class="form-label">Reason for Deletion *</label>
                                <textarea 
                                    class="form-control @error('reason') is-invalid @enderror" 
                                    id="reason"
                                    name="reason"
                                    rows="2"
                                    placeholder="e.g., Damaged, Obsolete, Lost"
                                    required
                                ></textarea>
                                @error('reason')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <button type="submit" class="btn btn-danger" onclick="return confirm('Are you sure? This cannot be undone.')">
                                <i class="bi bi-trash"></i> Delete Book
                            </button>
                        </form>
                    @else
                        <div class="alert alert-warning small mb-0">
                            <i class="bi bi-exclamation-circle"></i> 
                            Cannot delete this book — it has active borrowings. Return all copies first.
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card bg-light mb-3">
                <div class="card-body">
                    <h5 class="card-title">📖 Book Info</h5>
                    <p class="small"><strong>ID:</strong> {{ $book->formatted_id }}</p>
                    <p class="small"><strong>Created:</strong> {{ $book->created_at->format('M d, Y') }}</p>
                    <p class="small"><strong>Updated:</strong> {{ $book->updated_at->format('M d, Y H:i') }}</p>
                </div>
            </div>

            <div class="card bg-light">
                <div class="card-body">
                    <h5 class="card-title">⚡ Current Status</h5>
                    <p class="small mb-1"><strong>Status:</strong></p>
                    @if($book->status === 'Available')
                        <span class="badge bg-success">Available</span>
                    @elseif($book->status === 'Borrowed')
                        <span class="badge bg-warning text-dark">Borrowed</span>
                    @else
                        <span class="badge bg-danger">Overdue</span>
                    @endif
                    
                    <p class="small mt-3 mb-1"><strong>Available Copies:</strong></p>
                    <p class="small">{{ $book->quantity }} in stock</p>
                    
                    @if($book->borrowings()->active()->count() > 0)
                        <p class="small mt-3 mb-1"><strong>Active Borrowings:</strong></p>
                        <p class="small">{{ $book->borrowings()->active()->count() }} copy/ies borrowed</p>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
