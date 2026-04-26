@extends('layouts.app')

@section('content')
<div class="container-fluid mt-4">
    <!-- Page Header -->
    <div class="row mb-4">
        <div class="col-12">
            <h1 class="h3 mb-2">My Dashboard</h1>
            <p class="text-muted">Welcome, {{ Auth::guard('user')->user()->name }}!</p>
        </div>
    </div>

    <!-- Statistics Cards -->
    <div class="row mb-4">
        <div class="col-md-6 col-lg-3 mb-3">
            <div class="stat-card primary">
                <div class="stat-icon">
                    <i class="bi bi-book"></i>
                </div>
                <div class="stat-content">
                    <h6 class="text-muted">Active Borrows</h6>
                    <h3>{{ $activeBorrows }}</h3>
                    <small class="text-muted">Out of 3 allowed</small>
                </div>
            </div>
        </div>

        <div class="col-md-6 col-lg-3 mb-3">
            <div class="stat-card warning">
                <div class="stat-icon">
                    <i class="bi bi-clock-history"></i>
                </div>
                <div class="stat-content">
                    <h6 class="text-muted">Pending Requests</h6>
                    <h3>{{ $pendingRequests }}</h3>
                    <small class="text-muted">Awaiting approval</small>
                </div>
            </div>
        </div>

        <div class="col-md-6 col-lg-3 mb-3">
            <div class="stat-card danger">
                <div class="stat-icon">
                    <i class="bi bi-exclamation-circle"></i>
                </div>
                <div class="stat-content">
                    <h6 class="text-muted">Overdue</h6>
                    <h3>{{ $overdueBorrows }}</h3>
                    <small class="text-muted">Return ASAP</small>
                </div>
            </div>
        </div>

        <div class="col-md-6 col-lg-3 mb-3">
            <div class="stat-card info">
                <div class="stat-icon">
                    <i class="bi bi-cash-coin"></i>
                </div>
                <div class="stat-content">
                    <h6 class="text-muted">Total Penalties</h6>
                    <h3>₱{{ number_format($totalPenalties, 2) }}</h3>
                    <small class="text-muted">From past returns</small>
                </div>
            </div>
        </div>
    </div>

    <!-- Pending Requests Section -->
    @if($pendingBorrowings->count() > 0)
    <div class="row mb-4">
        <div class="col-12">
            <div class="card shadow-sm">
                <div class="card-header bg-warning text-dark">
                    <h5 class="mb-0">
                        <i class="bi bi-hourglass-split"></i> Pending Borrow Requests
                    </h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Book Title</th>
                                    <th>Requested Date</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($pendingBorrowings as $borrow)
                                <tr>
                                    <td>
                                        <strong>{{ $borrow->book->title }}</strong>
                                        <br>
                                        <small class="text-muted">by {{ $borrow->book->author }}</small>
                                    </td>
                                    <td>{{ $borrow->created_at->format('M d, Y') }}</td>
                                    <td>
                                        <span class="badge bg-warning">Pending</span>
                                    </td>
                                    <td>
                                        <a href="#" class="btn btn-sm btn-primary" data-bs-toggle="modal" 
                                           data-bs-target="#editBorrow{{ $borrow->id }}">
                                            <i class="bi bi-pencil"></i> Edit
                                        </a>
                                        <form action="{{ route('borrow.delete', $borrow->id) }}" method="POST" 
                                              style="display: inline;">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-danger" 
                                                    onclick="return confirm('Delete this request?')">
                                                <i class="bi bi-trash"></i> Delete
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endif

    <!-- Active Borrowings Section -->
    @if($approvedBorrowings->count() > 0)
    <div class="row mb-4">
        <div class="col-12">
            <div class="card shadow-sm">
                <div class="card-header bg-success text-white">
                    <h5 class="mb-0">
                        <i class="bi bi-check-circle"></i> My Active Borrowings
                    </h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Book Title</th>
                                    <th>Borrowed Date</th>
                                    <th>Due Date</th>
                                    <th>Days Left</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($approvedBorrowings as $borrow)
                                <tr class="{{ $borrow->status === 'Overdue' ? 'table-danger' : '' }}">
                                    <td>
                                        <strong>{{ $borrow->book->title }}</strong>
                                        <br>
                                        <small class="text-muted">by {{ $borrow->book->author }}</small>
                                    </td>
                                    <td>{{ $borrow->date_borrowed->format('M d, Y') }}</td>
                                    <td>{{ $borrow->due_date->format('M d, Y') }}</td>
                                    <td>
                                        @php
                                            $daysLeft = now()->diffInDays($borrow->due_date, false);
                                        @endphp
                                        @if($daysLeft < 0)
                                            <span class="badge bg-danger">{{ abs($daysLeft) }} days overdue</span>
                                        @else
                                            {{ $daysLeft }} days
                                        @endif
                                    </td>
                                    <td>
                                        @if($borrow->status === 'Borrowed')
                                            <span class="badge bg-success">Borrowed</span>
                                        @else
                                            <span class="badge bg-danger">Overdue</span>
                                        @endif
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endif

    <!-- Transaction History Section -->
    <div class="row">
        <div class="col-12">
            <div class="card shadow-sm">
                <div class="card-header bg-secondary text-white">
                    <h5 class="mb-0">
                        <i class="bi bi-list-ul"></i> Transaction History
                    </h5>
                </div>
                <div class="card-body">
                    @if($transactionHistory->count() > 0)
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Book Title</th>
                                    <th>Borrowed</th>
                                    <th>Returned</th>
                                    <th>Status</th>
                                    <th>Penalty</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($transactionHistory as $history)
                                <tr>
                                    <td>
                                        <strong>{{ $history->book->title }}</strong>
                                        <br>
                                        <small class="text-muted">by {{ $history->book->author }}</small>
                                    </td>
                                    <td>{{ $history->date_borrowed?->format('M d, Y') ?? 'N/A' }}</td>
                                    <td>{{ $history->return_date?->format('M d, Y') ?? 'N/A' }}</td>
                                    <td>
                                        @if($history->status === 'Returned')
                                            <span class="badge bg-success">Returned</span>
                                        @elseif($history->status === 'Rejected')
                                            <span class="badge bg-danger">Rejected</span>
                                        @else
                                            <span class="badge bg-danger">Overdue</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($history->penalty > 0)
                                            <strong class="text-danger">₱{{ number_format($history->penalty, 2) }}</strong>
                                        @else
                                            <span class="text-success">None</span>
                                        @endif
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination -->
                    <div class="mt-4">
                        {{ $transactionHistory->links() }}
                    </div>
                    @else
                    <div class="alert alert-info" role="alert">
                        <i class="bi bi-info-circle"></i> No transaction history yet.
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- Action Buttons -->
    <div class="row mt-4">
        <div class="col-12">
            <a href="{{ route('borrow.request') }}" class="btn btn-primary btn-lg">
                <i class="bi bi-plus-circle"></i> Request a Book
            </a>
            <a href="{{ route('user.profile') }}" class="btn btn-secondary btn-lg">
                <i class="bi bi-person-circle"></i> My Profile
            </a>
        </div>
    </div>
</div>

<!-- Edit Borrow Modal (placeholder) -->
@foreach($pendingBorrowings as $borrow)
<div class="modal fade" id="editBorrow{{ $borrow->id }}" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Edit Borrow Request</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form action="{{ route('borrow.update', $borrow->id) }}" method="POST">
                @csrf
                @method('PUT')
                <div class="modal-body">
                    <p class="text-muted mb-3">Change which book you want to request. Your current selection:</p>
                    <div class="alert alert-info">
                        <strong>{{ $borrow->book->title }}</strong>
                        <br>
                        <small>by {{ $borrow->book->author }}</small>
                    </div>
                    
                    <div class="mb-3">
                        <label for="book_id_{{ $borrow->id }}" class="form-label">Select a different book:</label>
                        <select name="book_id" id="book_id_{{ $borrow->id }}" class="form-select" required>
                            <option value="">-- Choose a book --</option>
                            @foreach($availableBooks as $book)
                                <option value="{{ $book->id }}" 
                                        {{ $borrow->book_id === $book->id ? 'selected' : '' }}>
                                    {{ $book->title }} 
                                    @if($book->quantity > 0)
                                        <span class="text-success">({{ $book->quantity }} available)</span>
                                    @else
                                        <span class="text-danger">(Out of stock)</span>
                                    @endif
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Update Request</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endforeach

<style>
    .stat-card {
        padding: 20px;
        border-radius: 8px;
        color: white;
        display: flex;
        align-items: center;
        gap: 15px;
        min-height: 100px;
    }

    .stat-card.primary { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); }
    .stat-card.warning { background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%); }
    .stat-card.danger { background: linear-gradient(135deg, #fa709a 0%, #fee140 100%); }
    .stat-card.info { background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%); }

    .stat-icon {
        font-size: 2.5rem;
        opacity: 0.8;
        flex-shrink: 0;
    }

    .stat-content h3 {
        font-size: 2rem;
        margin-bottom: 0;
    }

    .stat-content h6 {
        margin-bottom: 5px;
    }
</style>
@endsection
