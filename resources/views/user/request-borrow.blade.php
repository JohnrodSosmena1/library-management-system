@extends('layouts.app')

@section('content')
<div class="container-fluid mt-4">
    <!-- Page Header -->
    <div class="row mb-4">
        <div class="col-12">
            <h1 class="h3 mb-2">Request a Book</h1>
            <p class="text-muted">Browse available books and submit a request. The librarian will approve or reject your request.</p>
        </div>
    </div>

    @if($errors->any())
        <div class="alert alert-danger alert-dismissible fade show">
            <strong>Error:</strong>
            <ul class="mb-0">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show">
            {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <!-- Search & Filter -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card shadow-sm">
                <div class="card-body">
                    <form method="GET" class="row g-3">
                        <div class="col-md-6">
                            <input type="text" name="search" class="form-control" placeholder="Search by title or author..."
                                   value="{{ request('search') }}">
                        </div>
                        <div class="col-md-4">
                            <select name="category" class="form-select">
                                <option value="">-- All Categories --</option>
                                @foreach(\App\Models\Category::orderBy('name')->get() as $category)
                                    <option value="{{ $category->id }}" {{ request('category') == $category->id ? 'selected' : '' }}>
                                        {{ $category->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-2">
                            <button type="submit" class="btn btn-primary w-100">Search</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Books Grid -->
    @if($books->count() > 0)
    <div class="row">
        @foreach($books as $book)
        <div class="col-md-6 col-lg-4 col-xl-3 mb-4">
            <div class="card shadow-sm h-100 book-card">
                <div class="card-body d-flex flex-column">
                    <!-- Book Info -->
                    <h5 class="card-title mb-2">{{ $book->title }}</h5>
                    <p class="card-text text-muted mb-1">
                        <small><strong>Author:</strong> {{ $book->author }}</small>
                    </p>
                    <p class="card-text text-muted mb-1">
                        <small><strong>Category:</strong> {{ $book->category->name }}</small>
                    </p>
                    <p class="card-text text-muted mb-2">
                        <small><strong>ISBN:</strong> {{ $book->isbn }}</small>
                    </p>

                    <!-- Availability Status -->
                    <div class="mb-3">
                        @if($book->quantity > 0)
                            <span class="badge bg-success">Available ({{ $book->quantity }})</span>
                        @else
                            <span class="badge bg-danger">Out of Stock</span>
                        @endif
                    </div>

                    <!-- Request Button -->
                    <div class="mt-auto">
                        @php
                            $userPendingRequest = \Auth::guard('user')->user()->borrowings()
                                ->where('book_id', $book->id)
                                ->where('status', 'Pending')
                                ->first();
                        @endphp

                        @if($userPendingRequest)
                            <button type="button" class="btn btn-info w-100" data-bs-toggle="modal" 
                                    data-bs-target="#editBorrow{{ $userPendingRequest->id }}">
                                <i class="bi bi-pencil"></i> Change This Request
                            </button>
                        @else
                            <form action="{{ route('borrow.request.store') }}" method="POST">
                                @csrf
                                <input type="hidden" name="book_id" value="{{ $book->id }}">
                                <button type="submit" class="btn btn-primary w-100" 
                                        {{ $book->quantity <= 0 ? 'disabled' : '' }}>
                                    <i class="bi bi-check2-circle"></i> Request This Book
                                </button>
                            </form>
                        @endif
                    </div>
                </div>
            </div>
        </div>
        @endforeach
    </div>
    @else
    <div class="alert alert-info" role="alert">
        <i class="bi bi-info-circle"></i> No books found. Try adjusting your search filters.
    </div>
    @endif

    <!-- Back to Dashboard -->
    <div class="row mt-4">
        <div class="col-12">
            <a href="{{ route('dashboard.user') }}" class="btn btn-secondary">
                <i class="bi bi-arrow-left"></i> Back to Dashboard
            </a>
        </div>
    </div>
</div>

<style>
    .book-card {
        transition: transform 0.2s, box-shadow 0.2s;
        border: 1px solid #e0e0e0;
    }

    .book-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15) !important;
    }

    .book-card .card-title {
        height: 3rem;
        overflow: hidden;
        display: -webkit-box;
        -webkit-line-clamp: 2;
        -webkit-box-orient: vertical;
    }
</style>
@endsection
