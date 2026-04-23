@php
    $label = $label ?? $status;
    $cls   = match($status) {
        'Available', 'Returned', 'Active' => 'badge-available',
        'Borrowed'                         => 'badge-borrowed',
        'Overdue', 'Inactive'              => 'badge-overdue',
        default                            => 'badge-available',
    };
@endphp
<span class="badge {{ $cls }}">{{ $label }}</span>