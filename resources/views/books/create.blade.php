@extends('layouts.app')

@section('content')
<div class="container py-5">
    <h1 class="mb-4">📚 Add New Book</h1>

    <div class="row">
        <div class="col-md-8">
            <div class="card shadow-sm">
                <div class="card-body p-4">
                    <form action="{{ route('books.store') }}" method="POST">
                        @csrf

                        <div class="mb-3">
                            <label for="title" class="form-label">Book Title *</label>
                            <input 
                                type="text" 
                                class="form-control @error('title') is-invalid @enderror" 
                                id="title"
                                name="title"
                                placeholder="e.g., The Great Gatsby"
                                value="{{ old('title') }}"
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
                                placeholder="e.g., F. Scott Fitzgerald"
                                value="{{ old('author') }}"
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
                                    <option value="">-- Select Category --</option>
                                    @foreach($categories as $category)
                                        <option value="{{ $category->id }}" {{ old('category_id') == $category->id ? 'selected' : '' }}>
                                            {{ $category->name }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('category_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>

                            <div class="col-md-6 mb-3">
                                <label for="isbn" class="form-label">ISBN (Optional)</label>
                                <input 
                                    type="text" 
                                    class="form-control @error('isbn') is-invalid @enderror" 
                                    id="isbn"
                                    name="isbn"
                                    placeholder="e.g., 978-0-7432-7356-5"
                                    value="{{ old('isbn') }}"
                                >
                                @error('isbn')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="quantity" class="form-label">Quantity *</label>
                            <input 
                                type="number" 
                                class="form-control @error('quantity') is-invalid @enderror" 
                                id="quantity"
                                name="quantity"
                                min="1"
                                value="{{ old('quantity', 1) }}"
                                required
                            >
                            @error('quantity')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="mb-3">
                            <label for="description" class="form-label">Description (Optional)</label>
                            <textarea 
                                class="form-control @error('description') is-invalid @enderror" 
                                id="description"
                                name="description"
                                rows="4"
                                placeholder="Brief description of the book..."
                            >{{ old('description') }}</textarea>
                            @error('description')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-check-circle"></i> Add Book
                            </button>
                            <a href="{{ route('books.index') }}" class="btn btn-secondary">
                                <i class="bi bi-x-circle"></i> Cancel
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card bg-light">
                <div class="card-body">
                    <h5 class="card-title">📋 Requirements</h5>
                    <ul class="small">
                        <li><strong>Title:</strong> Book name (required)</li>
                        <li><strong>Author:</strong> Author name (required)</li>
                        <li><strong>Category:</strong> Book category (required)</li>
                        <li><strong>ISBN:</strong> International Standard Book Number (optional, unique)</li>
                        <li><strong>Quantity:</strong> Number of copies (required, min 1)</li>
                        <li><strong>Description:</strong> Additional info (optional)</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
