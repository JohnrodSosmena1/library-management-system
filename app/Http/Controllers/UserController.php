<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class UserController extends Controller
{
    public function index(Request $request): View
    {
        $query = User::withCount([
            'borrowings as active_count' => fn($q) => $q->active(),
        ]);

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        $users = $query->latest()->paginate(10)->withQueryString();

        return view('users.index', compact('users'));
    }

    public function create(): View
    {
        return view('users.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name'       => 'required|string|max:255',
            'email'      => 'required|email|unique:users,email',
            'contact_no' => 'nullable|string|max:20',
        ]);

        User::create($validated);

        return redirect()->route('users.index')
            ->with('success', "User \"{$validated['name']}\" registered successfully.");
    }

    public function edit(User $user): View
    {
        return view('users.edit', compact('user'));
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        $validated = $request->validate([
            'name'       => 'required|string|max:255',
            'email'      => "required|email|unique:users,email,{$user->id}",
            'contact_no' => 'nullable|string|max:20',
            'status'     => 'required|in:Active,Inactive',
        ]);

        $user->update($validated);

        return redirect()->route('users.index')
            ->with('success', "User \"{$user->name}\" updated.");
    }

    public function destroy(User $user): RedirectResponse
    {
        if ($user->activeBorrowings()->exists()) {
            return back()->with('error', "Cannot delete \"{$user->name}\" — they have active borrowings.");
        }

        $name = $user->name;
        $user->delete();

        return redirect()->route('users.index')
            ->with('success', "User \"{$name}\" deleted.");
    }

    /**
     * Show user profile (for logged-in users)
     */
    public function userProfile(): View
    {
        $user = \Auth::guard('user')->user();
        if (!$user) {
            return redirect('/login');
        }

        return view('user.profile', compact('user'));
    }

    /**
     * Update user profile
     */
    public function updateProfile(Request $request): RedirectResponse
    {
        $user = \Auth::guard('user')->user();
        if (!$user) {
            return redirect('/login');
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => "required|email|unique:users,email,{$user->id}",
            'contact_no' => 'required|string|max:20',
            'password' => 'nullable|string|min:8|confirmed',
        ]);

        $updateData = [
            'name' => $validated['name'],
            'email' => $validated['email'],
            'contact_no' => $validated['contact_no'],
        ];

        if ($validated['password']) {
            $updateData['password'] = \Illuminate\Support\Facades\Hash::make($validated['password']);
        }

        $user->update($updateData);

        return back()->with('success', 'Profile updated successfully!');
    }
}