@extends('layouts.app')

@section('content')
<div class="container py-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1>📚 Borrowed Books List</h1>
            <p class="text-muted mb-0">Currently borrowed items</p>
        </div>
    </div>

    <div class="card shadow-sm mb-4">
        <div class="card-body">
            <form method="GET" action="{{ route('borrowed-books.index') }}" class="row g-3">
                <div class="col-md-8">
                    <input
                        type="text"
                        name="search"
                        class="form-control"
                        placeholder="Search user, book..."
                        value="{{ request('search') }}"
                    >
                </div>
                <div class="col-md-4">
                    <button type="submit" class="btn btn-success w-100">
                        <i class="bi bi-search"></i> Search
                    </button>
                </div>
            </form>
        </div>
    </div>

    @if($borrowed->isEmpty())
        <div class="card shadow-sm">
            <div class="card-body text-center py-5">
                <div style="font-size: 3rem; margin-bottom: 1rem;">📖</div>
                <h5 class="text-muted">No borrowed books found</h5>
            </div>
        </div>
    @else
        <div class="card shadow-sm">
            <div class="card-header bg-light p-3">
                <span class="fw-bold">{{ $borrowed->total() }} records total</span>
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
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($borrowed as $row)
                            <tr>
                                <td><strong class="font-monospace">{{ $row->formatted_id }}</strong></td>
                                <td>{{ $row->user->name ?? '—' }}</td>
                                <td class="fw-bold">{{ $row->book->title ?? '—' }}</td>
                                <td class="text-muted font-monospace">{{ $row->date_borrowed?->format('M d, Y') ?? '—' }}</td>
                                <td class="text-muted font-monospace">{{ $row->due_date?->format('M d, Y') ?? '—' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="d-flex justify-content-center mt-4">
                {{ $borrowed->links('pagination') }}
            </div>
        </div>
    @endif

    <div class="mt-4">
        <a href="{{ route('transactions.index') }}" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left"></i> Back to Transactions
        </a>
    </div>
</div>
@endsection

