<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'LMS') — Library Management System</title>
    <link href="https://fonts.googleapis.com/css2?family=DM+Serif+Display:ital@0;1&family=DM+Sans:wght@300;400;500;600&family=JetBrains+Mono:wght@400;500&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @stack('styles')
</head>
<body>
<div class="app">
    {{-- SIDEBAR --}}
    <aside class="sidebar">
        <div class="sidebar-brand">
            <div class="brand-badge">
                <span class="brand-dot"></span>
                <span class="brand-name">LIC</span>
            </div>
            <div class="brand-sub">Library Management System</div>
        </div>

        <nav class="nav">
            <div class="nav-section">Overview</div>
            <a href="{{ route('dashboard') }}" class="nav-item {{ request()->routeIs('dashboard') ? 'active' : '' }}">
                <span class="nav-icon">⬛</span> Dashboard
            </a>

            <div class="nav-section">Catalog</div>
            <a href="{{ route('books.index') }}" class="nav-item {{ request()->routeIs('books.*') ? 'active' : '' }}">
                <span class="nav-icon">📖</span> Books
            </a>
            <a href="{{ route('categories.index') }}" class="nav-item {{ request()->routeIs('categories.*') ? 'active' : '' }}">
                <span class="nav-icon">⊞</span> Categories
            </a>

            <div class="nav-section">Circulation</div>
            <a href="{{ route('borrow.form') }}" class="nav-item {{ request()->routeIs('borrow.*') ? 'active' : '' }}">
                <span class="nav-icon">↗</span> Borrow Book
            </a>
            <a href="{{ route('return.form') }}" class="nav-item {{ request()->routeIs('return.*') ? 'active' : '' }}">
                <span class="nav-icon">↙</span> Return Book
                @php $overdueCount = \App\Models\Borrowing::overdue()->count(); @endphp
                @if($overdueCount > 0)
                    <span class="nav-badge">{{ $overdueCount }}</span>
                @endif
            </a>
            <a href="{{ route('transactions.index') }}" class="nav-item {{ request()->routeIs('transactions.*') ? 'active' : '' }}">
                <span class="nav-icon">≡</span> Transactions
            </a>

            <div class="nav-section">Admin</div>
            <a href="{{ route('users.index') }}" class="nav-item {{ request()->routeIs('users.*') ? 'active' : '' }}">
                <span class="nav-icon">◉</span> Users
            </a>
        </nav>

        <div class="sidebar-foot">
            <div class="librarian-card">
                <div class="librarian-av">JB</div>
                <div>
                    <div class="librarian-name">John Doe</div>
                    <div class="librarian-role">Head Librarian</div>
                </div>
            </div>
        </div>
    </aside>

    {{-- MAIN --}}
    <main class="main">
        <div class="topbar">
            <div class="topbar-left">
                <h1>@yield('page-title', 'Dashboard')</h1>
                <p>@yield('page-sub', 'Library and Information Center')</p>
            </div>
            <div class="topbar-right">
                @yield('topbar-actions')
            </div>
        </div>

        {{-- Flash Messages --}}
        @if(session('success'))
            <div class="flash flash-success">✔ {{ session('success') }}</div>
        @endif
        @if(session('error'))
            <div class="flash flash-error">✖ {{ session('error') }}</div>
        @endif

        <div class="content">
            @yield('content')
        </div>
    </main>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="{{ asset('js/app.js') }}"></script>
@stack('scripts')
</body>
</html>