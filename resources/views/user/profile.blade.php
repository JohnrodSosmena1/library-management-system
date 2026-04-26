@extends('layouts.app')

@section('content')
<div class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <!-- Profile Header -->
            <div class="card shadow-lg mb-4">
                <div class="card-header bg-primary text-white">
                    <h3 class="mb-0">
                        <i class="bi bi-person-circle"></i> My Profile
                    </h3>
                </div>
                <div class="card-body">
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

                    <div class="row mb-4">
                        <div class="col-md-4 text-center">
                            <div class="mb-3">
                                <i class="bi bi-person-circle display-1 text-primary"></i>
                            </div>
                            <h5>{{ $user->name }}</h5>
                            <p class="text-muted">Member ID: #{{ str_pad($user->id, 4, '0', STR_PAD_LEFT) }}</p>
                            <span class="badge bg-{{ $user->status === 'Active' ? 'success' : 'danger' }}">
                                {{ $user->status }}
                            </span>
                        </div>

                        <div class="col-md-8">
                            <form action="{{ route('user.profile.update') }}" method="POST">
                                @csrf
                                @method('PUT')

                                <div class="mb-3">
                                    <label for="name" class="form-label">Full Name</label>
                                    <input type="text" class="form-control @error('name') is-invalid @enderror" 
                                           id="name" name="name" value="{{ old('name', $user->name) }}" required>
                                    @error('name')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="mb-3">
                                    <label for="email" class="form-label">Email Address</label>
                                    <input type="email" class="form-control @error('email') is-invalid @enderror" 
                                           id="email" name="email" value="{{ old('email', $user->email) }}" required>
                                    @error('email')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="mb-3">
                                    <label for="contact_no" class="form-label">Contact Number</label>
                                    <input type="tel" class="form-control @error('contact_no') is-invalid @enderror" 
                                           id="contact_no" name="contact_no" value="{{ old('contact_no', $user->contact_no) }}" required>
                                    @error('contact_no')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <hr>

                                <h6 class="mb-3">Change Password (optional)</h6>

                                <div class="mb-3">
                                    <label for="password" class="form-label">New Password</label>
                                    <input type="password" class="form-control @error('password') is-invalid @enderror" 
                                           id="password" name="password" placeholder="Leave blank to keep current password">
                                    @error('password')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                    <small class="text-muted">Minimum 8 characters</small>
                                </div>

                                <div class="mb-4">
                                    <label for="password_confirmation" class="form-label">Confirm Password</label>
                                    <input type="password" class="form-control" 
                                           id="password_confirmation" name="password_confirmation" 
                                           placeholder="Confirm new password">
                                </div>

                                <button type="submit" class="btn btn-primary">
                                    <i class="bi bi-check-circle"></i> Save Changes
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Borrowing Statistics -->
            <div class="row mb-4">
                <div class="col-md-6">
                    <div class="card shadow-sm">
                        <div class="card-body text-center">
                            <i class="bi bi-book text-primary display-4"></i>
                            <h5 class="mt-3">Active Borrowings</h5>
                            <h2 class="text-primary">{{ $user->activeBorrowCount() }}</h2>
                            <small class="text-muted">out of 3 allowed</small>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="card shadow-sm">
                        <div class="card-body text-center">
                            <i class="bi bi-exclamation-circle text-danger display-4"></i>
                            <h5 class="mt-3">Overdue Books</h5>
                            <h2 class="text-danger">
                                {{ $user->borrowings()->where('status', 'Overdue')->count() }}
                            </h2>
                            <small class="text-muted">must return ASAP</small>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Registration Info -->
            <div class="card shadow-sm">
                <div class="card-header bg-light">
                    <h5 class="mb-0">
                        <i class="bi bi-info-circle"></i> Account Information
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <strong>Registered Since</strong>
                            <p class="text-muted">{{ $user->created_at->format('F d, Y') }}</p>
                        </div>
                        <div class="col-md-6">
                            <strong>Last Updated</strong>
                            <p class="text-muted">{{ $user->updated_at->format('F d, Y H:i') }}</p>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <strong>Account Status</strong>
                            <p>
                                <span class="badge bg-{{ $user->status === 'Active' ? 'success' : 'danger' }}">
                                    {{ $user->status }}
                                </span>
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Action Buttons -->
            <div class="row mt-4">
                <div class="col-12">
                    <a href="{{ route('dashboard.user') }}" class="btn btn-secondary">
                        <i class="bi bi-arrow-left"></i> Back to Dashboard
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
