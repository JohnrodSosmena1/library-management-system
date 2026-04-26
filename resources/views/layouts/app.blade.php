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
            @if(Auth::guard('user')->check())
                {{-- USER NAVIGATION --}}
                <div class="nav-section">My Account</div>
                <a href="{{ route('dashboard.user') }}" class="nav-item {{ request()->routeIs('dashboard.user') ? 'active' : '' }}">
                    <span class="nav-icon">📊</span> Dashboard
                </a>

                <div class="nav-section">Services</div>
                <a href="{{ route('borrow.request') }}" class="nav-item {{ request()->routeIs('borrow.request') ? 'active' : '' }}">
                    <span class="nav-icon">📖</span> Request Book
                </a>
                <a href="{{ route('user.profile') }}" class="nav-item {{ request()->routeIs('user.profile') ? 'active' : '' }}">
                    <span class="nav-icon">👤</span> My Profile
                </a>

                <div class="nav-section">Account</div>
            @else
                {{-- LIBRARIAN/ADMIN NAVIGATION --}}
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
                <a href="{{ route('borrow.pending') }}" class="nav-item {{ request()->routeIs('borrow.pending') ? 'active' : '' }}">
                    <span class="nav-icon">⏳</span> Pending Requests
                    @php $pendingCount = \App\Models\Borrowing::where('status', 'Pending')->count(); @endphp
                    @if($pendingCount > 0)
                        <span class="nav-badge">{{ $pendingCount }}</span>
                    @endif
                </a>
                <a href="{{ route('borrow.form') }}" class="nav-item {{ request()->routeIs('borrow.form') ? 'active' : '' }}">
                    <span class="nav-icon">↗</span> Borrow Book
                </a>
                <a href="{{ route('return.form') }}" class="nav-item {{ request()->routeIs('return.form') ? 'active' : '' }}">
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

                <div class="nav-section">Account</div>
            @endif
        </nav>

        <div class="sidebar-foot">
            @if(Auth::guard('user')->check())
                {{-- USER CARD --}}
                <div class="librarian-card">
                    <div class="librarian-av">{{ substr(Auth::guard('user')->user()->name, 0, 1) }}</div>
                    <div>
                        <div class="librarian-name">{{ Auth::guard('user')->user()->name }}</div>
                        <div class="librarian-role">Library Member</div>
                    </div>
                </div>
            @elseif(Auth::guard('librarian')->check())
                {{-- LIBRARIAN CARD --}}
                <div class="librarian-card">
                    <div class="librarian-av">{{ substr(Auth::guard('librarian')->user()->name, 0, 1) }}</div>
                    <div>
                        <div class="librarian-name">{{ Auth::guard('librarian')->user()->name }}</div>
                        <div class="librarian-role">{{ Auth::guard('librarian')->user()->role }}</div>
                    </div>
                </div>
            @else
                {{-- NOT LOGGED IN --}}
                <div class="librarian-card">
                    <div class="librarian-av">?</div>
                    <div>
                        <div class="librarian-name">Not Logged In</div>
                        <div class="librarian-role"><a href="{{ route('login') }}" class="text-decoration-none">Login</a></div>
                    </div>
                </div>
            @endif

            {{-- LOGOUT FORM --}}
            @if(Auth::guard('user')->check() || Auth::guard('librarian')->check())
                <form action="{{ route('logout') }}" method="POST" style="margin-top: 10px;">
                    @csrf
                    <button type="submit" class="btn btn-sm btn-outline-danger w-100">
                        <i class="bi bi-box-arrow-right"></i> Logout
                    </button>
                </form>
            @endif
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