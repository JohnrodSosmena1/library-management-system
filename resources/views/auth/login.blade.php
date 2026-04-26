@extends('layouts.app')

@section('content')
<div class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card shadow-lg">
                <div class="card-body p-5">
                    <h1 class="card-title text-center mb-4">Login</h1>

                    @if($errors->any())
                        <div class="alert alert-danger alert-dismissible fade show">
                            <strong>Error:</strong>
                            <ul class="mb-0">
                                @foreach($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    @endif

                    @if(session('success'))
                        <div class="alert alert-success alert-dismissible fade show">
                            {{ session('success') }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    @endif

                    <form action="{{ route('login') }}" method="POST">
                        @csrf

                        <div class="mb-3">
                            <label for="email" class="form-label">Email Address</label>
                            <input type="email" class="form-control @error('email') is-invalid @enderror" 
                                   id="email" name="email" value="{{ old('email') }}" required autofocus>
                            @error('email')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-4">
                            <label for="password" class="form-label">Password</label>
                            <input type="password" class="form-control @error('password') is-invalid @enderror" 
                                   id="password" name="password" required>
                            @error('password')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-4">
                            <label for="role" class="form-label">Login As</label>
                            <div class="btn-group w-100" role="group">
                                <input type="radio" class="btn-check" name="role" id="role_user" value="user" checked>
                                <label class="btn btn-outline-primary" for="role_user">User</label>

                                <input type="radio" class="btn-check" name="role" id="role_librarian" value="librarian">
                                <label class="btn btn-outline-primary" for="role_librarian">Librarian</label>
                            </div>
                        </div>

                        <div class="mb-3 form-check">
                            <input type="checkbox" class="form-check-input" id="remember" name="remember">
                            <label class="form-check-label" for="remember">
                                Remember me
                            </label>
                        </div>

                        <button type="submit" class="btn btn-primary w-100 mb-3">Login</button>
                    </form>

                    <hr>

                    <div class="alert alert-info" role="alert">
                        <strong>Test Credentials:</strong>
                        <ul class="mb-0 mt-2">
                            <li><strong>Admin:</strong> admin@library.local / admin123 (Librarian)</li>
                            <li><strong>New User:</strong> Register via the form</li>
                        </ul>
                    </div>

                    <p class="text-center mb-0">
                        Don't have an account? 
                        <a href="{{ route('register') }}" class="btn btn-link p-0">Register here</a>
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
