@extends('layouts.app')

@section('title', 'Return Book')
@section('page-title', 'Return a Book')
@section('page-sub', 'Record a book return and compute penalties')

@section('content')
<div class="form-wrap">
    <div class="panel">
        <div class="panel-head">
            <div class="panel-icon blue">↙</div>
            <div>
                <div class="panel-title">Process Book Return</div>
                <div class="panel-sub">Penalty rate: ₱{{ \App\Models\Borrowing::PENALTY_RATE }}.00 per day overdue</div>
            </div>
        </div>

        <form method="POST" action="{{ route('return.process') }}" class="form-body" id="return-form">
            @csrf

            {{-- Transaction Select --}}
            <div class="field">
                <label class="field-label">Active Transaction <span class="req">*</span></label>
                <select name="borrowing_id" id="txn-select"
                        class="{{ $errors->has('borrowing_id') ? 'is-error' : '' }}">
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
                            @if($txn->status === 'Overdue') [OVERDUE] @endif
                        </option>
                    @endforeach
                </select>
                @error('borrowing_id')<div class="field-error">{{ $message }}</div>@enderror
            </div>

            {{-- Transaction Detail Box --}}
            <div id="txn-detail" class="hidden">
                <div class="info-box" style="margin-bottom:14px">
                    <div class="info-row"><span class="info-label">User</span><span id="d-user"></span></div>
                    <div class="info-row"><span class="info-label">Book</span><span id="d-book"></span></div>
                    <div class="info-row"><span class="info-label">Date Borrowed</span><span id="d-borrowed"></span></div>
                    <div class="info-row"><span class="info-label">Due Date</span><span id="d-due"></span></div>
                    <div class="info-row"><span class="info-label">Status</span><span id="d-status"></span></div>
                </div>

                <div id="penalty-box" class="hidden">
                    <div class="alert-penalty">
                        <div class="penalty-title">Penalty Calculation</div>
                        <div class="info-row"><span>Days overdue</span><span id="p-days"></span></div>
                        <div class="info-row"><span>Rate per day</span><span>₱{{ \App\Models\Borrowing::PENALTY_RATE }}.00</span></div>
                        <div class="penalty-total"><span>Total Fine</span><span id="p-total"></span></div>
                    </div>
                </div>

                <div id="no-penalty-box" class="hidden">
                    <div class="alert alert-green">✔ No penalty — returned on time.</div>
                </div>
            </div>

            {{-- Return Fields --}}
            <div id="return-fields" class="hidden">
                <div class="field">
                    <label class="field-label">Return Date</label>
                    <input type="date" name="return_date" value="{{ old('return_date', date('Y-m-d')) }}">
                    @error('return_date')<div class="field-error">{{ $message }}</div>@enderror
                </div>
                <div class="field">
                    <label class="field-label">Book Condition</label>
                    <select name="book_condition">
                        <option>Good</option>
                        <option>Slightly damaged</option>
                        <option>Damaged</option>
                    </select>
                </div>
                <div class="form-actions">
                    <a href="{{ route('dashboard') }}" class="btn">Cancel</a>
                    <button type="submit" class="btn btn-primary">Confirm Return</button>
                </div>
            </div>
        </form>
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
    if (!this.value) {
        document.getElementById('txn-detail').classList.add('hidden');
        document.getElementById('return-fields').classList.add('hidden');
        return;
    }

    const daysLate = parseInt(opt.dataset.daysLate);
    const penalty  = parseFloat(opt.dataset.penalty);
    const status   = opt.dataset.status;

    document.getElementById('d-user').textContent     = opt.dataset.user;
    document.getElementById('d-book').textContent     = opt.dataset.book;
    document.getElementById('d-borrowed').textContent = fmtDate(opt.dataset.borrowed);
    document.getElementById('d-due').textContent      = fmtDate(opt.dataset.due);
    document.getElementById('d-status').innerHTML     = `<span style="color:${status==='Overdue'?'var(--red)':'var(--amber)'}; font-weight:600">${status}${daysLate>0?' — '+daysLate+' days late':''}</span>`;

    const penaltyBox   = document.getElementById('penalty-box');
    const noPenaltyBox = document.getElementById('no-penalty-box');

    if (daysLate > 0) {
        document.getElementById('p-days').textContent  = daysLate + ' days';
        document.getElementById('p-total').textContent = '₱' + penalty.toFixed(2);
        penaltyBox.classList.remove('hidden');
        noPenaltyBox.classList.add('hidden');
    } else {
        penaltyBox.classList.add('hidden');
        noPenaltyBox.classList.remove('hidden');
    }

    document.getElementById('txn-detail').classList.remove('hidden');
    document.getElementById('return-fields').classList.remove('hidden');
});
</script>
@endpush