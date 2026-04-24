@extends('layouts.app')

@section('content')
<div class="container py-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1>📋 Transactions</h1>
            <p class="text-muted mb-0">Complete borrowing history</p>
        </div>
    </div>

    <!-- Search & Filter Section -->
    <div class="card shadow-sm mb-4">
        <div class="card-body">
            <form method="GET" action="{{ route('transactions.index') }}" class="row g-3">
                <div class="col-md-5">
                    <input 
                        type="text" 
                        name="search" 
                        class="form-control" 
                        placeholder="Search user, book…"
                        value="{{ request('search') }}"
                    >
                </div>
                <div class="col-md-4">
                    <select name="status" class="form-select">
                        <option value="">-- All Status --</option>
                        <option value="Borrowed" {{ request('status') === 'Borrowed' ? 'selected' : '' }}>Borrowed</option>
                        <option value="Overdue" {{ request('status') === 'Overdue' ? 'selected' : '' }}>Overdue</option>
                        <option value="Returned" {{ request('status') === 'Returned' ? 'selected' : '' }}>Returned</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-success flex-grow-1">
                            <i class="bi bi-search"></i> Search
                        </button>
                        @if(request()->hasAny(['search','status']))
                            <a href="{{ route('transactions.index') }}" class="btn btn-outline-secondary">
                                <i class="bi bi-arrow-counterclockwise"></i> Clear
                            </a>
                        @endif
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Transactions Table -->
    @if($transactions->isEmpty())
        <div class="card shadow-sm">
            <div class="card-body text-center py-5">
                <div style="font-size: 3rem; margin-bottom: 1rem;">📋</div>
                <h5 class="text-muted">No transactions found</h5>
                <p class="text-muted mb-0">Try adjusting your search or filter criteria</p>
            </div>
        </div>
    @else
        <div class="card shadow-sm">
            <div class="card-header bg-light p-3">
                <span class="fw-bold">{{ $transactions->total() }} records total</span>
            </div>
            <div class="table-responsive">
                <table class="table table-hover table-striped mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Txn ID</th>
                            <th>User</th>
                            <th>Book</th>
                            <th>Borrowed</th>
                            <th>Due</th>
                            <th>Returned</th>
                            <th>Status</th>
                            <th>Fine</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($transactions as $txn)
                            <tr class="{{ $txn->status === 'Overdue' ? 'table-danger' : '' }}">
                                <td><strong class="font-monospace">{{ $txn->formatted_id }}</strong></td>
                                <td>{{ $txn->user->name }}</td>
                                <td class="fw-bold">{{ $txn->book->title }}</td>
                                <td class="text-muted font-monospace">{{ $txn->date_borrowed->format('M d, Y') }}</td>
                                <td class="text-muted font-monospace">{{ $txn->due_date->format('M d, Y') }}</td>
                                <td class="text-muted font-monospace">{{ $txn->return_date ? $txn->return_date->format('M d, Y') : '—' }}</td>
                                <td>
                                    @if($txn->status === 'Returned')
                                        <span class="badge bg-success">Returned</span>
                                    @elseif($txn->status === 'Overdue')
                                        <span class="badge bg-danger">Overdue</span>
                                    @else
                                        <span class="badge bg-warning text-dark">Borrowed</span>
                                    @endif
                                </td>
                                <td class="{{ $txn->days_late > 0 ? 'text-danger fw-bold' : 'text-muted' }}">
                                    {{ $txn->days_late > 0 ? '₱'.number_format($txn->computed_penalty, 2) : '—' }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Pagination -->
        <div class="d-flex justify-content-center mt-4">
            {{ $transactions->links('pagination') }}
        </div>
    @endif
</div>
@endsection