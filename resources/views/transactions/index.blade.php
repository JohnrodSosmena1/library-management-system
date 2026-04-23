@extends('layouts.app')

@section('title', 'Transactions')
@section('page-title', 'Transactions')
@section('page-sub', 'Complete borrowing history')

@section('content')
<div class="panel">
    <div class="panel-head">
        <div>
            <div class="panel-title">All Transactions</div>
            <div class="panel-sub">{{ $transactions->total() }} records total</div>
        </div>
    </div>

    <form method="GET" action="{{ route('transactions.index') }}" class="toolbar">
        <div class="search-wrap">
            <span class="search-icon">🔍</span>
            <input type="text" name="search" placeholder="Search user, book…"
                   value="{{ request('search') }}">
        </div>
        <select name="status" class="filter" onchange="this.form.submit()">
            <option value="">All Status</option>
            @foreach(['Borrowed','Overdue','Returned'] as $s)
                <option value="{{ $s }}" {{ request('status') === $s ? 'selected' : '' }}>{{ $s }}</option>
            @endforeach
        </select>
        <button type="submit" class="btn">Search</button>
        @if(request()->hasAny(['search','status']))
            <a href="{{ route('transactions.index') }}" class="btn">Clear</a>
        @endif
    </form>

    @if($transactions->isEmpty())
        <div class="empty">
            <div class="empty-icon">📋</div>
            <div class="empty-text">No transactions found</div>
        </div>
    @else
        <table>
            <thead>
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
                    <tr class="{{ $txn->status === 'Overdue' ? 'overdue-row' : '' }}">
                        <td class="mono">{{ $txn->formatted_id }}</td>
                        <td>{{ $txn->user->name }}</td>
                        <td class="bold">{{ $txn->book->title }}</td>
                        <td class="mono muted">{{ $txn->date_borrowed->format('M d, Y') }}</td>
                        <td class="mono muted">{{ $txn->due_date->format('M d, Y') }}</td>
                        <td class="mono muted">{{ $txn->return_date ? $txn->return_date->format('M d, Y') : '—' }}</td>
                        <td>@include('partials.badge', ['status' => $txn->status])</td>
                        <td class="{{ $txn->days_late > 0 ? 'red' : 'muted' }}">
                            {{ $txn->days_late > 0 ? '₱'.number_format($txn->computed_penalty, 2) : '—' }}
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
        <div class="pagination-wrap">
            {{ $transactions->links('partials.pagination') }}
        </div>
    @endif
</div>
@endsection