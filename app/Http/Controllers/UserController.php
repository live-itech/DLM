<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Spatie\Permission\Models\Role;

class UserController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [new Middleware('permission:users.manage')];
    }

    public function index()
    {
        $users = User::with('roles')->orderBy('name')->paginate(15);

        return view('users.index', compact('users'));
    }

    public function create()
    {
        return view('users.form', ['user' => new User(['is_active' => true]), 'roles' => Role::pluck('name')]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'role' => ['required', Rule::in(Role::pluck('name'))],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
            'is_active' => $request->boolean('is_active'),
            'email_verified_at' => now(),
        ]);
        $user->syncRoles($data['role']);

        return redirect()->route('users.index')->with('status', 'Pengguna berhasil ditambahkan.');
    }

    public function edit(User $user)
    {
        return view('users.form', ['user' => $user, 'roles' => Role::pluck('name')]);
    }

    public function update(Request $request, User $user)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'password' => ['nullable', 'string', 'min:8', 'confirmed'],
            'role' => ['required', Rule::in(Role::pluck('name'))],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $user->update([
            'name' => $data['name'],
            'email' => $data['email'],
            'is_active' => $request->boolean('is_active'),
        ]);
        if (! empty($data['password'])) {
            $user->update(['password' => Hash::make($data['password'])]);
        }
        $user->syncRoles($data['role']);

        return redirect()->route('users.index')->with('status', 'Pengguna berhasil diperbarui.');
    }

    public function destroy(Request $request, User $user)
    {
        abort_if($user->id === $request->user()->id, 422, 'Tidak bisa menghapus akun sendiri.');
        $user->delete();

        return back()->with('status', 'Pengguna dihapus.');
    }
}
