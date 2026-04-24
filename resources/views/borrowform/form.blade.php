@extends('layouts.app')

@section('content')
<div class="container py-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1>↗ Borrow Book</h1>
            <p class="text-muted mb-0">Process a borrowing request for a registered user</p>
        </div>
    </div>

    <div class="card shadow-sm mb-4">
        <div class="card-body">
            <div class="alert alert-info mb-4">
                <i class="bi bi-info-circle"></i> 
                <strong>Loan Details:</strong> Loan period: <strong>{{ \App\Models\Borrowing::LOAN_DAYS }} days</strong> · Penalty: <strong>₱{{ \App\Models\Borrowing::PENALTY_RATE }}/day</strong> overdue
            </div>

            <form method="POST" action="{{ route('borrow.store') }}" id="borrow-form">
                @csrf

                <!-- User Selection -->
                <div class="mb-3">
                    <label for="user-select" class="form-label">
                        User <span class="text-danger">*</span>
                    </label>
                    <select name="user_id" id="user-select" class="form-select {{ $errors->has('user_id') ? 'is-invalid' : '' }}">
                        <option value="">— Select a registered user —</option>
                        @foreach($users as $user)
                            <option value="{{ $user->id }}" {{ old('user_id') == $user->id ? 'selected' : '' }}>
                                {{ $user->name }} (USR-{{ str_pad($user->id, 3, '0', STR_PAD_LEFT) }})
                            </option>
                        @endforeach
                    </select>
                    @error('user_id')
                        <div class="invalid-feedback d-block">{{ $message }}</div>
                    @enderror
                </div>

                <!-- Eligibility Box -->
                <div id="eligibility-box" class="mb-3"></div>

                <!-- Book Selection -->
                <div class="mb-3">
                    <label for="book-select" class="form-label">
                        Book <span class="text-danger">*</span>
                    </label>
                    <select name="book_id" id="book-select" class="form-select {{ $errors->has('book_id') ? 'is-invalid' : '' }}">
                        <option value="">— Select an available book —</option>
                        @foreach($books as $book)
                            <option value="{{ $book->id }}" {{ old('book_id') == $book->id ? 'selected' : '' }}>
                                {{ $book->title }} — {{ $book->author }} ({{ $book->formatted_id }})
                            </option>
                        @endforeach
                    </select>
                    @error('book_id')
                        <div class="invalid-feedback d-block">{{ $message }}</div>
                    @enderror
                </div>

                <!-- Librarian -->
                <div class="mb-3">
                    <label for="librarian-select" class="form-label">Processed By</label>
                    <select name="librarian_id" id="librarian-select" class="form-select">
                        @foreach($librarians as $lib)
                            <option value="{{ $lib->id }}" {{ old('librarian_id') == $lib->id ? 'selected' : '' }}>
                                {{ $lib->name }} — {{ $lib->role }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <!-- Dates -->
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="date-borrowed" class="form-label">Date Borrowed</label>
                        <input type="date" name="date_borrowed" id="date-borrowed" class="form-control"
                               value="{{ old('date_borrowed', date('Y-m-d')) }}">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="due-date-display" class="form-label">Due Date (auto-calculated)</label>
                        <input type="date" id="due-date-display" class="form-control" readonly
                               value="{{ date('Y-m-d', strtotime('+30 days')) }}">
                    </div>
                </div>

                <!-- Transaction Summary -->
                <div id="txn-summary" class="card bg-light mb-4" style="display: none;">
                    <div class="card-body">
                        <h6 class="card-title">Transaction Summary</h6>
                        <div class="row">
                            <div class="col-md-6">
                                <p class="mb-1"><small class="text-muted">Loan Period</small></p>
                                <p class="fw-bold">{{ \App\Models\Borrowing::LOAN_DAYS }} days</p>
                            </div>
                            <div class="col-md-6">
                                <p class="mb-1"><small class="text-muted">Penalty Rate</small></p>
                                <p class="fw-bold text-danger">₱{{ \App\Models\Borrowing::PENALTY_RATE }}.00 / day overdue</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Form Actions -->
                <div class="d-flex gap-2">
                    <a href="{{ route('dashboard') }}" class="btn btn-outline-secondary">
                        <i class="bi bi-x-circle"></i> Cancel
                    </a>
                    <button type="submit" class="btn btn-primary" id="submit-btn">
                        <i class="bi bi-check-circle"></i> Confirm Borrow
                    </button>
                </div>
            </form>
        </div>
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