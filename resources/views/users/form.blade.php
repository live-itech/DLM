<x-app-layout>
    <x-slot name="header">{{ $user->exists ? 'Edit' : 'Tambah' }} Pengguna</x-slot>

    <x-page-header :title="($user->exists ? 'Edit' : 'Tambah') . ' Pengguna'" />

    <div class="card max-w-xl">
        <form method="POST" action="{{ $user->exists ? route('users.update', $user) : route('users.store') }}">
            @csrf
            @if ($user->exists) @method('PUT') @endif

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <div>
                    <label class="form-label">Nama <span class="text-red-500">*</span></label>
                    <input type="text" name="name" value="{{ old('name', $user->name) }}" class="form-input" required autofocus>
                    <x-input-error :messages="$errors->get('name')" class="mt-1" />
                </div>
                <div>
                    <label class="form-label">Email <span class="text-red-500">*</span></label>
                    <input type="email" name="email" value="{{ old('email', $user->email) }}" class="form-input" required>
                    <x-input-error :messages="$errors->get('email')" class="mt-1" />
                </div>
                <div>
                    <label class="form-label">Role <span class="text-red-500">*</span></label>
                    <select name="role" class="form-input" required>
                        @foreach ($roles as $role)
                            <option value="{{ $role }}" @selected(old('role', $user->roles->first()?->name) === $role)>{{ $role }}</option>
                        @endforeach
                    </select>
                    <x-input-error :messages="$errors->get('role')" class="mt-1" />
                </div>
                <div class="flex items-end">
                    <label class="inline-flex items-center gap-2">
                        <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $user->is_active ?? true)) class="rounded border-gray-300 text-gold focus:ring-gold">
                        <span class="text-sm text-gray-700">Akun aktif</span>
                    </label>
                </div>
                <div>
                    <label class="form-label">Password {{ $user->exists ? '' : '*' }}</label>
                    <input type="password" name="password" class="form-input" {{ $user->exists ? '' : 'required' }} autocomplete="new-password">
                    @if ($user->exists)<p class="mt-1 text-xs text-gray-400">Kosongkan bila tidak ingin mengganti.</p>@endif
                    <x-input-error :messages="$errors->get('password')" class="mt-1" />
                </div>
                <div>
                    <label class="form-label">Konfirmasi Password</label>
                    <input type="password" name="password_confirmation" class="form-input" autocomplete="new-password">
                </div>
            </div>

            <div class="mt-6 flex gap-2">
                <button class="btn-gold">Simpan</button>
                <a href="{{ route('users.index') }}" class="btn-outline">Batal</a>
            </div>
        </form>
    </div>
</x-app-layout>
