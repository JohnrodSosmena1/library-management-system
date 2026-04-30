@extends('layouts.app')

@section('title', 'Borrowing Management')
@section('page-title', 'Borrowing Management')
@section('page-sub', 'Review and approve/reject pending borrow requests')

@section('content')
<div class="container-fluid mt-4">
    <!-- Page Header -->
    <div class="row mb-4">
        <div class="col-12">
            <h1 class="h3 mb-2">Pending Borrow Requests</h1>
            <p class="text-muted">Review and approve or reject user borrow requests.</p>
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

<!-- Search -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card shadow-sm">
                <div class="card-body">
                    <form method="GET" class="row g-3">
                        <div class="col-md-9">
                            <input type="text" name="search" class="form-control" placeholder="Search by user name or book title..."
                                   value="{{ request('search') }}">
                        </div>
                        <div class="col-md-3">
                            <button type="submit" class="btn btn-primary w-100">Search</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Borrowing Table -->
    <div class="row">
        <div class="col-12">
            <div class="card shadow-sm">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="bi bi-arrow-left-right me-2"></i>
                        Borrowing Records ({{ $borrowings->total() }})
                    </h5>
                </div>

                @if($borrowings->count() > 0)
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>User ID</th>
                                <th>Name</th>
                                <th>Book (ISBN)</th>
                                <th>Status</th>
                                <th>Borrow Date</th>
                                <th>Due Date</th>
                                <th>Return Date</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($borrowings as $borrowing)
                            <tr>
                                <td>
                                    <span class="fw-bold">USR-{{ str_pad($borrowing->user->id, 3, '0', STR_PAD_LEFT) }}</span>
                                </td>
                                <td>
                                    <strong>{{ $borrowing->user->name }}</strong>
                                </td>
                                <td>
                                    <div class="fw-bold">{{ $borrowing->book->title }}</div>
                                    <small class="text-muted">ISBN: {{ $borrowing->book->isbn ?? 'N/A' }}</small>
                                </td>
                                <td>
                                    @php
                                        $statusClass = match($borrowing->status) {
                                            'Pending' => 'warning',
                                            'Borrowed' => 'primary',
                                            'Overdue' => 'danger',
                                            'Returned' => 'success',
                                            'Rejected' => 'secondary',
                                            default => 'secondary'
                                        };
                                    @endphp
                                    <span class="badge bg-{{ $statusClass }}">{{ $borrowing->status }}</span>
                                </td>
                                <td>
                                    {{ $borrowing->date_borrowed ? $borrowing->date_borrowed->format('M d, Y') : 'N/A' }}
                                </td>
                                <td>
                                    {{ $borrowing->due_date ? $borrowing->due_date->format('M d, Y') : 'N/A' }}
                                </td>
                                <td>
                                    {{ $borrowing->return_date ? $borrowing->return_date->format('M d, Y') : 'N/A' }}
                                </td>
                                <td>
                                    <div class="btn-group" role="group">
                                        @if($borrowing->status === 'Pending')
                                            <!-- Approve Button -->
                                            <button class="btn btn-sm btn-success" data-bs-toggle="modal" 
                                                    data-bs-target="#approveModal{{ $borrowing->id }}">
                                                <i class="bi bi-check2-circle"></i> Approve
                                            </button>

                                            <!-- Reject Button -->
                                            <button type="button" class="btn btn-sm btn-danger" 
                                                    onclick="rejectBorrowing({{ $borrowing->id }})">
                                                <i class="bi bi-x-circle"></i> Reject
                                            </button>
                                        @endif

@if(in_array($borrowing->status, ['Borrowed', 'Overdue']))
                                            <!-- Edit Button -->
                                            <button class="btn btn-sm btn-primary" data-bs-toggle="modal" 
                                                    data-bs-target="#editModal{{ $borrowing->id }}">
                                                <i class="bi bi-pencil"></i> Edit
                                            </button>
                                        @endif

                                        <!-- Delete Button -->
                                        <button type="button" class="btn btn-sm btn-dark" 
                                                onclick="deleteBorrowing({{ $borrowing->id }})">
                                            <i class="bi bi-trash"></i> Delete
                                        </button>

                                        @if($borrowing->status === 'Overdue')
                                            <span class="badge bg-danger ms-2">
                                                <i class="bi bi-exclamation-triangle"></i> ₱{{ number_format($borrowing->computed_penalty, 2) }}
                                            </span>
                                        @endif
                                    </div>
                                </td>
                            </tr>

                            <!-- Approve Modal -->
                            @if($borrowing->status === 'Pending')
                            <div class="modal fade" id="approveModal{{ $borrowing->id }}" tabindex="-1">
                                <div class="modal-dialog">
                                    <div class="modal-content">
                                        <div class="modal-header">
                                            <h5 class="modal-title">Approve Borrowing Request</h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                        </div>
                                        <form action="{{ route('borrowing.approve', $borrowing->id) }}" method="POST">
                                            @csrf
                                            @method('PUT')
                                            <div class="modal-body">
                                                <p><strong>User:</strong> {{ $borrowing->user->name }}</p>
                                                <p><strong>Book:</strong> {{ $borrowing->book->title }}</p>

                                                <div class="mb-3">
                                                    <label for="date_borrowed_{{ $borrowing->id }}" class="form-label">Date Borrowed</label>
                                                    <input type="date" class="form-control" 
                                                           id="date_borrowed_{{ $borrowing->id }}" name="date_borrowed" 
                                                           value="{{ now()->format('Y-m-d') }}" required>
                                                </div>

                                                <div class="mb-3">
                                                    <label for="librarian_id_{{ $borrowing->id }}" class="form-label">Processed By</label>
                                                    <select class="form-select" id="librarian_id_{{ $borrowing->id }}" 
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
                                                <button type="submit" class="btn btn-success">Approve</button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>
                            @endif

                            <!-- Edit Modal -->
                            @if(in_array($borrowing->status, ['Borrowed', 'Overdue']))
                            <div class="modal fade" id="editModal{{ $borrowing->id }}" tabindex="-1">
                                <div class="modal-dialog">
                                    <div class="modal-content">
                                        <div class="modal-header">
                                            <h5 class="modal-title">Edit Borrowing</h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                        </div>
                                        <form action="{{ route('borrowing.update', $borrowing->id) }}" method="POST">
                                            @csrf
                                            @method('PUT')
                                            <div class="modal-body">
                                                <p><strong>User:</strong> {{ $borrowing->user->name }}</p>
                                                <p><strong>Book:</strong> {{ $borrowing->book->title }}</p>

                                                <div class="mb-3">
                                                    <label for="due_date_{{ $borrowing->id }}" class="form-label">Due Date</label>
                                                    <input type="date" class="form-control" 
                                                           id="due_date_{{ $borrowing->id }}" name="due_date" 
                                                           value="{{ $borrowing->due_date ? $borrowing->due_date->format('Y-m-d') : '' }}" required>
                                                </div>

                                                <div class="mb-3">
                                                    <label for="librarian_id_edit_{{ $borrowing->id }}" class="form-label">Processed By</label>
                                                    <select class="form-select" id="librarian_id_edit_{{ $borrowing->id }}" 
                                                            name="librarian_id" required>
                                                        <option value="">-- Select Librarian --</option>
                                                        @foreach(\App\Models\Librarian::orderBy('name')->get() as $librarian)
                                                            <option value="{{ $librarian->id }}" 
                                                                    {{ $borrowing->librarian_id === $librarian->id ? 'selected' : '' }}>
                                                                {{ $librarian->name }} ({{ $librarian->role }})
                                                            </option>
                                                        @endforeach
                                                    </select>
                                                </div>
                                            </div>
                                            <div class="modal-footer">
                                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                <button type="submit" class="btn btn-primary">Update</button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>
                            @endif
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div class="card-footer bg-light">
                    {{ $borrowings->links() }}
                </div>
                @else
                <div class="card-body">
                    <div class="alert alert-info mb-0" role="alert">
                        <i class="bi bi-info-circle"></i> No borrowing records found.
                    </div>
                </div>
                @endif
            </div>
        </div>
    </div>

    <!-- Back to Dashboard -->
    <div class="row mt-4">
        <div class="col-12">
            <a href="{{ route('dashboard') }}" class="btn btn-secondary">
                <i class="bi bi-arrow-left"></i> Back to Dashboard
            </a>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

// Reject borrowing via AJAX
function rejectBorrowing(borrowingId) {
    if (!confirm('Reject this borrowing request?')) return;

    const btn = event.target;
    const originalHtml = btn.innerHTML;
    btn.disabled = true;
    btn.innerHTML = '<i class="spinner-border spinner-border-sm"></i>...';

    fetch(`/borrowing/${borrowingId}/reject`, {
        method: 'PUT',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': csrfToken,
            'Accept': 'application/json',
        },
    })
    .then(response => {
        if (!response.ok) {
            return response.json().then(data => {
                throw new Error(data.error || 'Request failed');
            });
        }
        return response.json();
    })
.then(data => {
        // Fade out and remove the row
        const row = btn.closest('tr');
        row.style.transition = 'opacity 0.3s ease';
        row.style.opacity = '0';
        setTimeout(() => {
            row.remove();
            // Check if table is empty now
            const tbody = document.querySelector('table tbody');
            if (!tbody || tbody.children.length === 0) {
                location.reload();
            } else {
                // Redirect to transactions page after successful rejection
                window.location.href = '{{ route("transactions.index") }}';
            }
        }, 300);

        // Show success message
        showAlert('success', data.success);
    })
    .catch(error => {
        console.error('Error:', error);
        btn.disabled = false;
        btn.innerHTML = originalHtml;
        alert('Error rejecting request: ' + error.message);
    });
}

// Delete borrowing via AJAX
function deleteBorrowing(borrowingId) {
    if (!confirm('Are you sure you want to delete this borrowing record? This action cannot be undone.')) return;

    const btn = event.target;
    const originalHtml = btn.innerHTML;
    btn.disabled = true;
    btn.innerHTML = '<i class="spinner-border spinner-border-sm"></i>...';

    fetch(`/borrowing/${borrowingId}`, {
        method: 'DELETE',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': csrfToken,
            'Accept': 'application/json',
        },
    })
    .then(response => {
        if (!response.ok) {
            return response.json().then(data => {
                throw new Error(data.error || 'Request failed');
            });
        }
        return response.json();
    })
    .then(data => {
        // Fade out and remove the row
        const row = btn.closest('tr');
        row.style.transition = 'opacity 0.3s ease';
        row.style.opacity = '0';
        setTimeout(() => {
            row.remove();
            // Check if table is empty now
            const tbody = document.querySelector('table tbody');
            if (!tbody || tbody.children.length === 0) {
                location.reload();
            }
        }, 300);

        // Show success message
        showAlert('success', data.success);
    })
    .catch(error => {
        console.error('Error:', error);
        btn.disabled = false;
        btn.innerHTML = originalHtml;
        alert('Error deleting record: ' + error.message);
    });
}

// Show alert message
function showAlert(type, message) {
    const alertDiv = document.createElement('div');
    alertDiv.className = `alert alert-${type} alert-dismissible fade show`;
    alertDiv.setAttribute('role', 'alert');
    alertDiv.innerHTML = `
        <i class="bi bi-${type === 'success' ? 'check-circle' : 'exclamation-circle'}"></i> ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;
    const container = document.querySelector('.container-fluid');
    const pageHeader = container.querySelector('.row.mb-4');
    pageHeader.insertAdjacentElement('afterend', alertDiv);
}
</script>
@endpush
