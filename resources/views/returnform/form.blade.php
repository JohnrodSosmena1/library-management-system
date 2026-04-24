@extends('layouts.app')

@section('content')
<div class="container py-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1>↙ Return Book</h1>
            <p class="text-muted mb-0">Record a book return and compute penalties</p>
        </div>
    </div>

    <div class="card shadow-sm">
        <div class="card-body">
            <div class="alert alert-warning mb-4">
                <i class="bi bi-exclamation-triangle"></i>
                <strong>Penalty Rate:</strong> ₱{{ \App\Models\Borrowing::PENALTY_RATE }}.00 per day overdue
            </div>

            <form method="POST" action="{{ route('return.process') }}" id="return-form">
                @csrf

                <!-- Transaction Selection -->
                <div class="mb-3">
                    <label for="txn-select" class="form-label">
                        Active Transaction <span class="text-danger">*</span>
                    </label>
                    <select name="borrowing_id" id="txn-select" class="form-select {{ $errors->has('borrowing_id') ? 'is-invalid' : '' }}">
                        <option value="">— Select a transaction —</option>
                        @foreach($activeBorrowings as $txn)
                            <option value="{{ $txn->id }}"
                                    data-user="{{ $txn->user->name }}"
                                    data-book="{{ $txn->book->title }}"
                                    data-borrowed="{{ $txn->date_borrowed->format('Y-m-d') }}"
                                    data-due="{{ $txn->due_date->format('Y-m-d') }}"
                                    data-status="{{ $txn->status }}"
                                    data-days-late="{{ $txn->days_late }}"
                                    data-penalty="{{ $txn->computed_penalty }}"
                                    {{ old('borrowing_id') == $txn->id ? 'selected' : '' }}>
                                {{ $txn->formatted_id }} — {{ $txn->user->name }} — {{ $txn->book->title }}
                                @if($txn->status === 'Overdue') <span class="badge bg-danger">OVERDUE</span> @endif
                            </option>
                        @endforeach
                    </select>
                    @error('borrowing_id')
                        <div class="invalid-feedback d-block">{{ $message }}</div>
                    @enderror
                </div>

                <!-- Transaction Detail Box -->
                <div id="txn-detail" style="display: none;" class="mb-4">
                    <!-- Transaction Details Card -->
                    <div class="card bg-light mb-3">
                        <div class="card-body">
                            <h6 class="card-title">Transaction Details</h6>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <p class="mb-1"><small class="text-muted">User</small></p>
                                    <p class="fw-bold" id="d-user">—</p>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <p class="mb-1"><small class="text-muted">Book</small></p>
                                    <p class="fw-bold" id="d-book">—</p>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <p class="mb-1"><small class="text-muted">Date Borrowed</small></p>
                                    <p class="fw-bold" id="d-borrowed">—</p>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <p class="mb-1"><small class="text-muted">Due Date</small></p>
                                    <p class="fw-bold" id="d-due">—</p>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-12 mb-0">
                                    <p class="mb-1"><small class="text-muted">Status</small></p>
                                    <p class="fw-bold" id="d-status">—</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Penalty Box -->
                    <div id="penalty-box" style="display: none;" class="mb-3">
                        <div class="alert alert-danger">
                            <h6 class="alert-heading mb-2">
                                <i class="bi bi-exclamation-circle"></i> Penalty Calculation
                            </h6>
                            <div class="row">
                                <div class="col-md-6">
                                    <p class="mb-1"><small>Days Overdue</small></p>
                                    <p class="fw-bold mb-0" id="p-days">—</p>
                                </div>
                                <div class="col-md-6">
                                    <p class="mb-1"><small>Rate per Day</small></p>
                                    <p class="fw-bold mb-0">₱{{ \App\Models\Borrowing::PENALTY_RATE }}.00</p>
                                </div>
                            </div>
                            <hr class="my-2">
                            <div class="row">
                                <div class="col-md-6">
                                    <p class="mb-0"><small>Total Fine</small></p>
                                </div>
                                <div class="col-md-6 text-end">
                                    <p class="fw-bold text-danger mb-0 fs-5" id="p-total">₱0.00</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- No Penalty Box -->
                    <div id="no-penalty-box" style="display: none;" class="mb-3">
                        <div class="alert alert-success">
                            <i class="bi bi-check-circle"></i> <strong>✓ No penalty</strong> — Book returned on time!
                        </div>
                    </div>
                </div>

                <!-- Return Fields -->
                <div id="return-fields" style="display: none;">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="return-date" class="form-label">Return Date</label>
                            <input type="date" name="return_date" id="return-date" class="form-control"
                                   value="{{ old('return_date', date('Y-m-d')) }}">
                            @error('return_date')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="book-condition" class="form-label">Book Condition</label>
                            <select name="book_condition" id="book-condition" class="form-select">
                                <option value="Good">Good</option>
                                <option value="Slightly damaged">Slightly Damaged</option>
                                <option value="Damaged">Damaged</option>
                            </select>
                        </div>
                    </div>

                    <!-- Form Actions -->
                    <div class="d-flex gap-2 mt-4">
                        <a href="{{ route('dashboard') }}" class="btn btn-outline-secondary">
                            <i class="bi bi-x-circle"></i> Cancel
                        </a>
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-check-circle"></i> Confirm Return
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
function fmtDate(str) {
    const d = new Date(str + 'T00:00:00');
    return d.toLocaleDateString('en-PH', { month: 'short', day: 'numeric', year: 'numeric' });
}

document.getElementById('txn-select').addEventListener('change', function () {
    const opt = this.options[this.selectedIndex];
    const txnDetail = document.getElementById('txn-detail');
    const returnFields = document.getElementById('return-fields');
    
    if (!this.value) {
        txnDetail.style.display = 'none';
        returnFields.style.display = 'none';
        return;
    }

    const daysLate = parseInt(opt.dataset.daysLate);
    const penalty  = parseFloat(opt.dataset.penalty);
    const status   = opt.dataset.status;

    document.getElementById('d-user').textContent     = opt.dataset.user;
    document.getElementById('d-book').textContent     = opt.dataset.book;
    document.getElementById('d-borrowed').textContent = fmtDate(opt.dataset.borrowed);
    document.getElementById('d-due').textContent      = fmtDate(opt.dataset.due);
    
    const statusBadge = status === 'Overdue' 
        ? `<span class="badge bg-danger">${status} — ${daysLate} days late</span>`
        : `<span class="badge bg-success">${status}</span>`;
    document.getElementById('d-status').innerHTML = statusBadge;

    const penaltyBox   = document.getElementById('penalty-box');
    const noPenaltyBox = document.getElementById('no-penalty-box');

    if (daysLate > 0) {
        document.getElementById('p-days').textContent  = daysLate + ' days';
        document.getElementById('p-total').textContent = '₱' + penalty.toFixed(2);
        penaltyBox.style.display = 'block';
        noPenaltyBox.style.display = 'none';
    } else {
        penaltyBox.style.display = 'none';
        noPenaltyBox.style.display = 'block';
    }

    txnDetail.style.display = 'block';
    returnFields.style.display = 'block';
});
</script>
@endpush