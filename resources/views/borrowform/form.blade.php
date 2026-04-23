@extends('layouts.app')

@section('title', 'Borrow Book')
@section('page-title', 'Borrow a Book')
@section('page-sub', 'Process a borrowing request for a registered user')

@section('content')
<div class="form-wrap">
    <div class="panel">
        <div class="panel-head">
            <div class="panel-icon green">↗</div>
            <div>
                <div class="panel-title">New Borrowing Transaction</div>
                <div class="panel-sub">Loan period: {{ \App\Models\Borrowing::LOAN_DAYS }} days · Penalty: ₱{{ \App\Models\Borrowing::PENALTY_RATE }}/day</div>
            </div>
        </div>

        <form method="POST" action="{{ route('borrow.store') }}" class="form-body" id="borrow-form">
            @csrf

            {{-- User --}}
            <div class="field">
                <label class="field-label">User <span class="req">*</span></label>
                <select name="user_id" id="user-select"
                        class="{{ $errors->has('user_id') ? 'is-error' : '' }}">
                    <option value="">— Select a registered user —</option>
                    @foreach($users as $user)
                        <option value="{{ $user->id }}" {{ old('user_id') == $user->id ? 'selected' : '' }}>
                            {{ $user->name }} (USR-{{ str_pad($user->id, 3, '0', STR_PAD_LEFT) }})
                        </option>
                    @endforeach
                </select>
                @error('user_id')<div class="field-error">{{ $message }}</div>@enderror
            </div>

            {{-- Eligibility Box --}}
            <div id="eligibility-box"></div>

            {{-- Book --}}
            <div class="field">
                <label class="field-label">Book <span class="req">*</span></label>
                <select name="book_id" id="book-select"
                        class="{{ $errors->has('book_id') ? 'is-error' : '' }}">
                    <option value="">— Select an available book —</option>
                    @foreach($books as $book)
                        <option value="{{ $book->id }}" {{ old('book_id') == $book->id ? 'selected' : '' }}>
                            {{ $book->title }} — {{ $book->author }} ({{ $book->formatted_id }})
                        </option>
                    @endforeach
                </select>
                @error('book_id')<div class="field-error">{{ $message }}</div>@enderror
            </div>

            {{-- Librarian --}}
            <div class="field">
                <label class="field-label">Processed By</label>
                <select name="librarian_id">
                    @foreach($librarians as $lib)
                        <option value="{{ $lib->id }}" {{ old('librarian_id') == $lib->id ? 'selected' : '' }}>
                            {{ $lib->name }} — {{ $lib->role }}
                        </option>
                    @endforeach
                </select>
            </div>

            {{-- Dates --}}
            <div class="row2">
                <div class="field">
                    <label class="field-label">Date Borrowed</label>
                    <input type="date" name="date_borrowed" id="date-borrowed"
                           value="{{ old('date_borrowed', date('Y-m-d')) }}">
                </div>
                <div class="field">
                    <label class="field-label">Due Date (auto)</label>
                    <input type="date" id="due-date-display" readonly class="readonly"
                           value="{{ date('Y-m-d', strtotime('+30 days')) }}">
                </div>
            </div>

            {{-- Transaction Summary --}}
            <div id="txn-summary" class="hidden">
                <div class="info-box">
                    <div class="info-row">
                        <span class="info-label">Loan period</span>
                        <span>{{ \App\Models\Borrowing::LOAN_DAYS }} days</span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Penalty rate</span>
                        <span class="red">₱{{ \App\Models\Borrowing::PENALTY_RATE }}.00 / day overdue</span>
                    </div>
                </div>
            </div>

            <div class="form-actions">
                <a href="{{ route('dashboard') }}" class="btn">Cancel</a>
                <button type="submit" class="btn btn-primary" id="submit-btn">Confirm Borrow</button>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

// Eligibility check on user select
document.getElementById('user-select').addEventListener('change', function () {
    const userId = this.value;
    const box    = document.getElementById('eligibility-box');
    if (!userId) { box.innerHTML = ''; return; }

    fetch('{{ route("borrow.eligibility") }}', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': csrfToken,
        },
        body: JSON.stringify({ user_id: userId }),
    })
    .then(r => r.json())
    .then(data => {
        const cls  = data.eligible ? 'alert-green' : 'alert-red';
        const icon = data.eligible ? '✔' : '✖';
        box.innerHTML = `<div class="alert ${cls}">${icon} ${data.message}</div>`;
        document.getElementById('submit-btn').disabled = !data.eligible;
    });
});

// Auto-compute due date
document.getElementById('date-borrowed').addEventListener('change', function () {
    const d   = new Date(this.value);
    d.setDate(d.getDate() + {{ \App\Models\Borrowing::LOAN_DAYS }});
    document.getElementById('due-date-display').value = d.toISOString().split('T')[0];
});

// Show summary when book selected
document.getElementById('book-select').addEventListener('change', function () {
    const summary = document.getElementById('txn-summary');
    summary.classList.toggle('hidden', !this.value);
});
</script>
@endpush