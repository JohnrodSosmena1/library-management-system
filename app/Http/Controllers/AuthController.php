<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Librarian;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    /**
     * Show the registration form
     */
    public function registerForm()
    {
        return view('auth.register');
    }

    /**
     * Handle user registration
     */
    public function register(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users|max:255',
            'password' => 'required|string|min:8|confirmed',
            'contact_no' => 'required|string|max:20',
        ]);

        try {
            $user = User::create([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'password' => Hash::make($validated['password']),
                'contact_no' => $validated['contact_no'],
                'status' => 'Active',
            ]);

            // Auto-login after registration
            Auth::guard('user')->login($user);

            return redirect('/dashboard')->with('success', 'Registration successful! Welcome to the library.');
        } catch (\Exception $e) {
            return back()->withErrors(['error' => 'Registration failed. Please try again.']);
        }
    }

    /**
     * Show the login form
     */
    public function loginForm()
    {
        return view('auth.login');
    }

    /**
     * Handle login for both users and librarians
     */
    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
            'role' => 'required|in:user,librarian',
        ]);

        $credentials = $request->only('email', 'password');
        $role = $request->input('role');

        if ($role === 'librarian') {
            if (Auth::guard('librarian')->attempt($credentials, $request->filled('remember'))) {
                return redirect('/')->with('success', 'Login successful!');
            }
        } else {
            if (Auth::guard('user')->attempt($credentials, $request->filled('remember'))) {
                return redirect('/dashboard')->with('success', 'Login successful!');
            }
        }

        throw ValidationException::withMessages([
            'email' => 'The provided credentials do not match our records.',
        ]);
    }

    /**
     * Handle logout
     */
    public function logout(Request $request)
    {
        // Logout from all guards
        if (Auth::guard('user')->check()) {
            Auth::guard('user')->logout();
        } elseif (Auth::guard('librarian')->check()) {
            Auth::guard('librarian')->logout();
        }

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/')->with('success', 'Logged out successfully!');
    }
}
