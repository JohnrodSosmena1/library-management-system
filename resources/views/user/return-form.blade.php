@extends('layouts.app')

@section('page-title', 'Return My Book')
@section('page-sub', 'Return borrowed books and check penalties')

@section('content')
<div class="container-fluid mt-4">
    <div class="row justify-content-center">
        <div class="col-md-8 col-lg-6">
            <div class="card shadow-lg">
                <div class="card-header bg-primary text-white text-center">
                    <h1 class="h3 mb-0">↙ Return My Book</h1>
                    <p class="text-white-50 mb-0">Record return for your active borrowings and compute any penalties</p>
                </div>

                @if (session('success'))
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        {{ session('success') }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                @endif

                @if (session('error'))
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        {{ session('error') }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                @endif

                <div class="card-body p-4">
                    @if($activeBorrowings->count() === 0)
                        <div class="text-center py-5">
                            <i class="bi bi-check-circle-fill text-success" style="font-size: 4rem;"></i>
                            <h3 class="mt-3 text-muted">No Active Borrowings</h3>
                            <p class="text-muted">You have no borrowed books to return.</p>
                            <a href="{{ route('dashboard.user') }}" class="btn btn-primary">
                                <i class="bi bi-arrow-left"></i> Back to Dashboard
                            </a>
                        </div>
                    @else
                        <form method="POST" action="{{ route('user.return.process') }}" id="return-form">
                            @csrf
                            <div class="mb-4">
                                <label for="borrowing_id" class="form-label fw-bold">Select Borrowing to Return</label>
                                <select name="borrowing_id" id="txn-select" class="form-select {{ $errors->has('borrowing_id') ? 'is-invalid' : '' }}" required>
                                    <option value="">— Select an active borrowing —</option>
                                    @foreach($activeBorrowings as $txn)
                                        <option value="{{ $txn->id }}"
                                                data-book="{{ $txn->book->title }}"
                                                data-author="{{ $txn->book->author }}"
                                                data-borrowed="{{ $txn->date_borrowed?->format('Y-m-d') }}"
                                                data-due="{{ $txn->due_date?->format('Y-m-d') }}"

                                                data-penalty="{{ $txn->computed_penalty }}"
                                                {{ old('borrowing_id') == $txn->id ? 'selected' : '' }}>
                                            {{ $txn->formatted_id }} — {{ $txn->book->title }} ({{ $txn->days_late }} days {{ $txn->days_late > 0 ? 'overdue' : 'remaining' }})
                                        </option>
                                    @endforeach
                                </select>
                                @error('borrowing_id')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                            </div>

                            <!-- Transaction Details -->
                            <div id="txn-detail" style="display: none;">
                                <div class="card bg-light mb-4">
                                    <div class="card-body">
                                        <div class="row">
                                            <div class="col-md-8">
                                                <h6 class="fw-bold mb-1" id="d-book"></h6>
                                                <small class="text-muted" id="d-author"></small>
                                            </div>
                                            <div class="col-md-4 text-end">
                                                <span class="badge {{ session('error') ? 'bg-danger' : 'bg-info' }}" id="txn-status">
                                                    Active
                                                </span>
                                            </div>
                                        </div>
                                        <hr>
                                        <div class="row text-center">
                                            <div class="col-md-6">
                                                <p class="mb-1"><small class="text-muted">Borrowed</small></p>
                                                <p class="fw-bold" id="d-borrowed">—</p>
                                            </div>
                                            <div class="col-md-6">
                                                <p class="mb-1"><small class="text-muted">Due Date</small></p>
                                                <p class="fw-bold" id="d-due">—</p>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Penalty Preview -->
                                <div id="penalty-preview" class="alert" style="display: none;" role="alert">
                                    <div class="d-flex align-items-center">
                                        <i class="bi bi-cash-coin me-2"></i>
                                        <div>
                                            <strong id="penalty-amount">₱0.00</strong>
                                            <small id="penalty-reason" class="text-muted d-block"></small>
                                        </div>
                                    </div>
                                </div>

                                <!-- Return Date -->
                                <div class="mb-3">
                                    <label for="return_date" class="form-label">Return Date <small class="text-muted">(Today)</small></label>
                                    <input type="date" name="return_date" id="return-date" class="form-control {{ $errors->has('return_date') ? 'is-invalid' : '' }}"
                                           value="{{ old('return_date', now()->format('Y-m-d')) }}" required>
                                    @error('return_date')
                                        <div class="invalid-feedback d-block">{{ $message }}</div>
                                    @enderror
                                </div>

                                <!-- Book Condition -->
                                <div class="mb-4">
                                    <label class="form-label">Book Condition</label>
                                    <div>
                                        <div class="form-check form-check-inline">
                                            <input class="form-check-input" type="radio" name="condition" id="cond-good" value="Good" checked>
                                            <label class="form-check-label" for="cond-good">Good</label>
                                        </div>
                                        <div class="form-check form-check-inline">
                                            <input class="form-check-input" type="radio" name="condition" id="cond-slight" value="Slightly damaged">
                                            <label class="form-check-label" for="cond-slight">Slightly damaged</label>
                                        </div>
                                        <div class="form-check form-check-inline">
                                            <input class="form-check-input" type="radio" name="condition" id="cond-damaged" value="Damaged">
                                            <label class="form-check-label" for="cond-damaged">Damaged</label>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                                <a href="{{ route('dashboard.user') }}" class="btn btn-secondary me-md-2">
                                    <i class="bi bi-arrow-left"></i> Back to Dashboard
                                </a>
                                <button type="submit" class="btn btn-success" id="submit-btn" disabled>
                                    <i class="bi bi-check-lg"></i> Complete Return
                                </button>
                            </div>
                        </form>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const txnSelect = document.getElementById('txn-select');
    const txnDetail = document.getElementById('txn-detail');
    const penaltyPreview = document.getElementById('penalty-preview');
    const submitBtn = document.getElementById('submit-btn');
    const returnDateInput = document.getElementById('return-date');

    function fmtDate(str) {
        if (!str) return '—';
        const d = new Date(str + 'T00:00:00');
        return d.toLocaleDateString('en-PH', { month: 'short', day: 'numeric', year: 'numeric' });
    }

    txnSelect.addEventListener('change', function() {
        const opt = this.selectedOptions[0];
        if (!opt || !opt.value) {
            txnDetail.style.display = 'none';
            penaltyPreview.style.display = 'none';
            submitBtn.disabled = true;
            return;
        }

        document.getElementById('d-book').textContent = opt.dataset.book;
        document.getElementById('d-author').textContent = 'by ' + opt.dataset.author;
        document.getElementById('d-borrowed').textContent = fmtDate(opt.dataset.borrowed);
        document.getElementById('d-due').textContent = fmtDate(opt.dataset.due);

        const penalty = parseFloat(opt.dataset.penalty) || 0;
        const daysLate = opt.dataset.daysLate || 0;  // Add data-days-late if needed

        txnDetail.style.display = 'block';
        submitBtn.disabled = false;

        if (penalty > 0) {
            document.getElementById('penalty-amount').textContent = '₱' + penalty.toFixed(2);
            document.getElementById('penalty-reason').textContent = daysLate + ' days late × ₱5/day';
            penaltyPreview.className = 'alert alert-warning';
            penaltyPreview.style.display = 'block';
        } else {
            penaltyPreview.style.display = 'none';
        }
    });

    // Auto-set return date to today
    returnDateInput.value = new Date().toISOString().split('T')[0];

    // Listen for return date changes (recompute if needed)
    returnDateInput.addEventListener('change', function() {
        // Future logic for return date vs due date if dynamic penalty
    });
});
</script>

<style>
.card-header {
    border-bottom: 1px solid rgba(255,255,255,0.1) !important;
}
.form-select.is-invalid {
    border-color: #dc3545;
}
.alert {
    border-radius: 8px;
}
</style>
@endsection

