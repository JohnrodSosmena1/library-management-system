@extends('layouts.app')

@section('content')
<div class="container-fluid mt-4">
    <!-- Page Header -->
    <div class="row mb-4">
        <div class="col-12">
            <h1 class="h3 mb-2">Pending Borrow Requests</h1>
            <p class="text-muted">Review and approve/reject user borrow requests.</p>
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

    <!-- Search & Filter -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card shadow-sm">
                <div class="card-body">
                    <form method="GET" class="row g-3">
                        <div class="col-md-8">
                            <input type="text" name="search" class="form-control" placeholder="Search by user name or book title..."
                                   value="{{ request('search') }}">
                        </div>
                        <div class="col-md-4">
                            <button type="submit" class="btn btn-primary w-100">Search</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Pending Requests Table -->
    <div class="row">
        <div class="col-12">
            <div class="card shadow-sm">
                <div class="card-header bg-warning text-dark">
                    <h5 class="mb-0">
                        <i class="bi bi-hourglass-split"></i> 
                        Pending Requests ({{ $pendingRequests->total() }})
                    </h5>
                </div>

                @if($pendingRequests->count() > 0)
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>User Name</th>
                                <th>Contact</th>
                                <th>Book Title</th>
                                <th>Author</th>
                                <th>Requested Date</th>
                                <th>Available Qty</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($pendingRequests as $request)
                            <tr>
                                <td>
                                    <strong>{{ $request->user->name }}</strong>
                                </td>
                                <td>
                                    <small class="text-muted">{{ $request->user->contact_no }}</small>
                                </td>
                                <td>
                                    <strong>{{ $request->book->title }}</strong>
                                </td>
                                <td>
                                    <small class="text-muted">{{ $request->book->author }}</small>
                                </td>
                                <td>
                                    {{ $request->created_at->format('M d, Y H:i') }}
                                </td>
                                <td>
                                    @if($request->book->quantity > 0)
                                        <span class="badge bg-success">{{ $request->book->quantity }}</span>
                                    @else
                                        <span class="badge bg-danger">Out of Stock</span>
                                    @endif
                                </td>
                                <td>
                                    @if($request->book->quantity > 0)
                                        <!-- Approve Button (triggers modal) -->
                                        <button class="btn btn-sm btn-success" data-bs-toggle="modal" 
                                                data-bs-target="#approveModal{{ $request->id }}">
                                            <i class="bi bi-check2-circle"></i> Approve
                                        </button>

                                        <!-- Reject Button -->
                                        <form action="{{ route('borrow.reject', $request->id) }}" method="POST" style="display: inline;">
                                            @csrf
                                            @method('PUT')
                                            <button type="submit" class="btn btn-sm btn-danger" 
                                                    onclick="return confirm('Reject this request?')">
                                                <i class="bi bi-x-circle"></i> Reject
                                            </button>
                                        </form>
                                    @else
                                        <!-- Out of Stock -->
                                        <form action="{{ route('borrow.reject', $request->id) }}" method="POST" style="display: inline;">
                                            @csrf
                                            @method('PUT')
                                            <button type="submit" class="btn btn-sm btn-secondary" 
                                                    onclick="return confirm('Out of stock — reject this request?')">
                                                <i class="bi bi-x-circle"></i> Out of Stock
                                            </button>
                                        </form>
                                    @endif
                                </td>
                            </tr>

                            <!-- Approve Modal -->
                            <div class="modal fade" id="approveModal{{ $request->id }}" tabindex="-1">
                                <div class="modal-dialog">
                                    <div class="modal-content">
                                        <div class="modal-header">
                                            <h5 class="modal-title">Approve Borrow Request</h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                        </div>
                                        <form action="{{ route('borrow.approve', $request->id) }}" method="POST">
                                            @csrf
                                            @method('PUT')
                                            <div class="modal-body">
                                                <p><strong>User:</strong> {{ $request->user->name }}</p>
                                                <p><strong>Book:</strong> {{ $request->book->title }}</p>

                                                <div class="mb-3">
                                                    <label for="date_borrowed_{{ $request->id }}" class="form-label">Date Borrowed</label>
                                                    <input type="date" class="form-control" 
                                                           id="date_borrowed_{{ $request->id }}" name="date_borrowed" 
                                                           value="{{ now()->format('Y-m-d') }}" required>
                                                </div>

                                                <div class="mb-3">
                                                    <label for="librarian_id_{{ $request->id }}" class="form-label">Processed By</label>
                                                    <select class="form-select" id="librarian_id_{{ $request->id }}" 
                                                            name="librarian_id" required>
                                                        <option value="">-- Select Librarian --</option>
                                                        @foreach(\App\Models\Librarian::orderBy('name')->get() as $librarian)
                                                            <option value="{{ $librarian->id }}" 
                                                                    {{ auth('librarian')->user()->id === $librarian->id ? 'selected' : '' }}>
                                                                {{ $librarian->name }} ({{ $librarian->role }})
                                                            </option>
                                                        @endforeach
                                                    </select>
                                                </div>
                                            </div>
                                            <div class="modal-footer">
                                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                <button type="submit" class="btn btn-success">Approve Request</button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div class="card-footer bg-light">
                    {{ $pendingRequests->links() }}
                </div>
                @else
                <div class="card-body">
                    <div class="alert alert-info mb-0" role="alert">
                        <i class="bi bi-info-circle"></i> No pending requests at the moment.
                    </div>
                </div>
                @endif
            </div>
        </div>
    </div>

    <!-- Back Button -->
    <div class="row mt-4">
        <div class="col-12">
            <a href="{{ route('dashboard') }}" class="btn btn-secondary">
                <i class="bi bi-arrow-left"></i> Back to Dashboard
            </a>
        </div>
    </div>
</div>
@endsection
