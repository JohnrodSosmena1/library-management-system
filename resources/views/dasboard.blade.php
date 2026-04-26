@extends('layouts.app')

@section('title', 'Dashboard')
@section('page-title', 'Dashboard')
@section('page-sub', 'Overview of library operations')

@section('content')
<div class="container-fluid">
    {{-- Statistics Cards --}}
    <div class="row g-4 mb-4">
        {{-- Total Books Card --}}
        <div class="col-12 col-sm-6 col-lg-3">
            <div class="stat-card primary">
                <div class="stat-icon">
                    <i class="bi bi-book"></i>
                </div>
                <div class="stat-content">
                    <h6>Total Books</h6>
                    <h3>{{ $totalBooks }}</h3>
                    <small><i class="bi bi-info-circle me-1"></i>{{ $uniqueTitles }} unique titles</small>
                </div>
            </div>
        </div>

        {{-- Borrowed Books Card --}}
        <div class="col-12 col-sm-6 col-lg-3">
            <div class="stat-card warning">
                <div class="stat-icon">
                    <i class="bi bi-arrow-right-circle"></i>
                </div>
                <div class="stat-content">
                    <h6>Currently Borrowed</h6>
                    <h3>{{ $borrowed }}</h3>
                    <small><i class="bi bi-info-circle me-1"></i>active loans</small>
                </div>
            </div>
        </div>

        {{-- Overdue Books Card --}}
        <div class="col-12 col-sm-6 col-lg-3">
            <div class="stat-card danger">
                <div class="stat-icon">
                    <i class="bi bi-exclamation-circle"></i>
                </div>
                <div class="stat-content">
                    <h6>Overdue Books</h6>
                    <h3>{{ $overdue }}</h3>
                    <small><i class="bi bi-alert me-1"></i>need follow-up</small>
                </div>
            </div>
        </div>

        {{-- Total Users Card --}}
        <div class="col-12 col-sm-6 col-lg-3">
            <div class="stat-card success">
                <div class="stat-icon">
                    <i class="bi bi-people"></i>
                </div>
                <div class="stat-content">
                    <h6>Registered Users</h6>
                    <h3>{{ $totalUsers }}</h3>
                    <small><i class="bi bi-info-circle me-1"></i>library members</small>
                </div>
            </div>
        </div>

        {{-- Pending Requests Card --}}
        <div class="col-12 col-sm-6 col-lg-3">
            <a href="{{ route('borrow.pending') }}" class="stat-card info" style="text-decoration: none; color: white; cursor: pointer;">
                <div class="stat-icon">
                    <i class="bi bi-hourglass-split"></i>
                </div>
                <div class="stat-content">
                    <h6>Pending Requests</h6>
                    <h3>{{ $pendingRequests }}</h3>
                    <small><i class="bi bi-info-circle me-1"></i>awaiting approval</small>
                </div>
            </a>
        </div>
    </div>

    {{-- Main Content --}}
    <div class="row g-4">
        {{-- Recent Activity --}}
        <div class="col-12 col-lg-8">
            <div class="card">
                <div class="card-header d-flex align-items-center">
                    <i class="bi bi-arrow-repeat me-2"></i>
                    <div>
                        <h5 class="mb-0">Recent Activity</h5>
                        <small>Latest borrowing transactions</small>
                    </div>
                </div>
                <div class="card-body p-0">
                    @forelse($recentActivity as $txn)
                        @php
                            $isReturn  = $txn->status === 'Returned';
                            $isOverdue = $txn->status === 'Overdue';
                            $color     = $isReturn ? 'success' : ($isOverdue ? 'danger' : 'warning');
                            $icon      = $isReturn ? 'check-circle-fill' : ($isOverdue ? 'exclamation-triangle-fill' : 'arrow-right-circle-fill');
                        @endphp
                        <div class="list-group-item border-start-3 border-{{ $color }}">
                            <div class="d-flex justify-content-between align-items-start">
                                <div class="flex-grow-1">
                                    <div class="mb-2">
                                        @if($isReturn)
                                            <span class="fw-bold text-dark">{{ $txn->user->name }}</span>
                                            <span class="text-muted">returned</span>
                                            <span class="fw-bold text-dark">{{ $txn->book->title }}</span>
                                        @elseif($isOverdue)
                                            <span class="fw-bold text-dark">{{ $txn->book->title }}</span>
                                            <span class="text-muted">is overdue —</span>
                                            <span class="fw-bold text-dark">{{ $txn->user->name }}</span>
                                        @else
                                            <span class="fw-bold text-dark">{{ $txn->user->name }}</span>
                                            <span class="text-muted">borrowed</span>
                                            <span class="fw-bold text-dark">{{ $txn->book->title }}</span>
                                        @endif
                                    </div>
                                    <small class="text-muted d-block">
                                        <i class="bi bi-calendar3 me-1"></i>
                                        {{ $txn->borrow_date?->format('M d, Y') ?? 'N/A' }}
                                        @if($txn->due_date)
                                            <span class="ms-3"><i class="bi bi-calendar-event me-1"></i>Due: {{ $txn->due_date->format('M d, Y') }}</span>
                                        @endif
                                    </small>
                                </div>
                                <span class="badge bg-{{ $color }} ms-3">
                                    <i class="bi bi-{{ $icon }} me-1"></i>{{ $txn->status }}
                                </span>
                            </div>
                        </div>
                    @empty
                        <div class="text-center py-5">
                            <i class="bi bi-inbox text-muted display-5"></i>
                            <p class="text-muted mt-3 mb-0">No activity yet</p>
                        </div>
                    @endforelse
                </div>
            </div>
        </div>

        {{-- Quick Stats Sidebar --}}
        <div class="col-12 col-lg-4">
            {{-- Status Overview --}}
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="bi bi-graph-up me-2"></i>Status Overview
                    </h5>
                </div>
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <span class="text-muted">Books in Stock</span>
                        <span class="badge bg-primary">{{ $totalBooks > 0 ? round(($totalBooks - $borrowed) / $totalBooks * 100) : 0 }}%</span>
                    </div>
                    <div class="progress mb-4">
                        <div class="progress-bar bg-primary" role="progressbar" style="width: {{ $totalBooks > 0 ? round(($totalBooks - $borrowed) / $totalBooks * 100) : 0 }}%"></div>
                    </div>

                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <span class="text-muted">Return Rate</span>
                        <span class="badge bg-success">{{ $borrowed > 0 ? round(($borrowed) / ($borrowed + ($overdue || 0)) * 100) : 0 }}%</span>
                    </div>
                    <div class="progress">
                        <div class="progress-bar bg-success" role="progressbar" style="width: {{ $borrowed > 0 ? round(($borrowed) / ($borrowed + ($overdue || 0)) * 100) : 0 }}%"></div>
                    </div>
                </div>
            </div>

            {{-- Quick Actions --}}
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="bi bi-lightning me-2"></i>Quick Actions
                    </h5>
                </div>
                <div class="list-group list-group-flush">
                    <a href="{{ route('borrow.form') }}" class="list-group-item list-group-item-action d-flex align-items-center">
                        <i class="bi bi-arrow-right-circle text-primary me-2"></i>
                        <span>Borrow Book</span>
                        <i class="bi bi-chevron-right ms-auto text-muted"></i>
                    </a>
                    <a href="{{ route('return.form') }}" class="list-group-item list-group-item-action d-flex align-items-center">
                        <i class="bi bi-arrow-left-circle text-success me-2"></i>
                        <span>Return Book</span>
                        <i class="bi bi-chevron-right ms-auto text-muted"></i>
                    </a>
                    <a href="{{ route('books.index') }}" class="list-group-item list-group-item-action d-flex align-items-center">
                        <i class="bi bi-book text-info me-2"></i>
                        <span>View Books</span>
                        <i class="bi bi-chevron-right ms-auto text-muted"></i>
                    </a>
                    <a href="{{ route('transactions.index') }}" class="list-group-item list-group-item-action d-flex align-items-center">
                        <i class="bi bi-receipt text-warning me-2"></i>
                        <span>Transactions</span>
                        <i class="bi bi-chevron-right ms-auto text-muted"></i>
                    </a>
                </div>
            </div>
        </div>
    </div>

    {{-- Pending Requests Section --}}
    @if($pendingRequests > 0)
        <div class="row g-4 mt-2">
            <div class="col-12">
                <div class="card border-warning">
                    <div class="card-header bg-warning bg-opacity-10">
                        <h5 class="mb-0 text-warning">
                            <i class="bi bi-hourglass-split me-2"></i>Pending Borrow Requests ({{ $pendingRequests }})
                        </h5>
                    </div>
                    <div class="card-body p-0">
                        @if($recentPendingRequests->isNotEmpty())
                            <div class="table-responsive">
                                <table class="table table-hover mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th>User</th>
                                            <th>Book Title</th>
                                            <th>Requested</th>
                                            <th>Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($recentPendingRequests as $req)
                                            <tr>
                                                <td>
                                                    <div class="fw-bold">{{ $req->user->name }}</div>
                                                    <small class="text-muted">{{ $req->user->contact_no }}</small>
                                                </td>
                                                <td>
                                                    <div class="fw-bold">{{ $req->book->title }}</div>
                                                    <small class="text-muted">{{ $req->book->author ?? 'N/A' }}</small>
                                                </td>
                                                <td>
                                                    <span class="text-muted">{{ $req->created_at->format('M d, Y H:i') }}</span>
                                                </td>
                                                <td>
                                                    <a href="{{ route('borrow.pending') }}" class="btn btn-sm btn-warning">
                                                        <i class="bi bi-arrow-right"></i> Review All
                                                    </a>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @else
                            <div class="p-3">
                                <p class="text-muted mb-0">No pending requests to display.</p>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    @endif

    {{-- Overdue Books Section --}}
    @if($overdueList->isNotEmpty())
        <div class="row g-4 mt-2">
            <div class="col-12">
                <div class="card border-danger">
                    <div class="card-header bg-danger bg-opacity-10">
                        <h5 class="mb-0 text-danger">
                            <i class="bi bi-exclamation-triangle-fill me-2"></i>Overdue Books - Immediate Attention Required
                        </h5>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>User</th>
                                        <th>Book Title</th>
                                        <th>Due Date</th>
                                        <th>Days Late</th>
                                        <th>Fine</th>
                                        <th>Contact</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($overdueList as $txn)
                                        <tr class="table-danger-light">
                                            <td>
                                                <div class="fw-bold">{{ $txn->user->name }}</div>
                                                <small class="text-muted">ID: {{ $txn->user->id }}</small>
                                            </td>
                                            <td>
                                                <div class="fw-bold">{{ $txn->book->title }}</div>
                                                <small class="text-muted">{{ $txn->book->author ?? 'N/A' }}</small>
                                            </td>
                                            <td>
                                                <span class="text-muted">{{ $txn->due_date->format('M d, Y') }}</span>
                                            </td>
                                            <td>
                                                <span class="badge bg-danger">
                                                    <i class="bi bi-calendar-x me-1"></i>{{ now()->diffInDays($txn->due_date) }}d
                                                </span>
                                            </td>
                                            <td>
                                                <span class="text-danger fw-bold">₱{{ number_format($txn->computed_penalty ?? 0, 2) }}</span>
                                            </td>
                                            <td>
                                                <small class="text-muted">{{ $txn->user->contact_no ?? 'N/A' }}</small>
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
</div>

<style>
    .border-start-3 {
        border-left-width: 4px !important;
    }

    .list-group-item {
        padding: 1rem 1.25rem;
    }

    .display-5 {
        font-size: 3rem;
    }
</style>
@endsection