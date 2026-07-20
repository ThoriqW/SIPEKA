@extends('layouts.admin')

@section('content')
<div class="py-6">
    <div class="max-w-2xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="mb-6">
            <h1 class="text-2xl font-semibold text-gray-900">Edit User</h1>
            <p class="text-sm text-gray-500 mt-1">Perbarui data akun user</p>
        </div>

        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
            <form method="POST" action="{{ route('admin.user.update', $user) }}">
                @csrf
                @method('PUT')

                <!-- Info Pegawai (read-only) -->
                <div class="mb-4 p-4 bg-gray-50 rounded-md">
                    <h3 class="text-sm font-medium text-gray-700 mb-2">Informasi Pegawai</h3>
                    <table class="text-sm w-full">
                        <tr>
                            <td class="text-gray-500 w-20">NIP</td>
                            <td class="font-medium">{{ $user->nip ?? '—' }}</td>
                        </tr>
                        <tr>
                            <td class="text-gray-500">Nama</td>
                            <td class="font-medium">{{ $user->name }}</td>
                        </tr>
                        <tr>
                            <td class="text-gray-500">OPD</td>
                            <td class="font-medium">{{ $pegawai->opd->nama_opd ?? '—' }}</td>
                        </tr>
                    </table>
                </div>

                <!-- Role -->
                <div class="mb-4">
                    <label for="role" class="block text-sm font-medium text-gray-700">Role</label>
                    <select id="role" name="role" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        <option value="user" {{ old('role', $user->role) == 'user' ? 'selected' : '' }}>User (Biasa)</option>
                        <option value="bkd" {{ old('role', $user->role) == 'bkd' ? 'selected' : '' }}>Super Admin (BKD)</option>
                    </select>
                    @error('role')<p class="text-red-500 text-sm mt-1">{{ $message }}</p>@enderror
                </div>

                <!-- Status Aktif -->
                <div class="mb-4">
                    <label for="is_active" class="block text-sm font-medium text-gray-700">Status</label>
                    <select id="is_active" name="is_active" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        <option value="1" {{ old('is_active', $user->is_active) == 1 ? 'selected' : '' }}>Aktif</option>
                        <option value="0" {{ old('is_active', $user->is_active) == 0 ? 'selected' : '' }}>Nonaktif</option>
                    </select>
                    @error('is_active')<p class="text-red-500 text-sm mt-1">{{ $message }}</p>@enderror
                </div>

                <!-- Password Baru (opsional) -->
                <div class="mb-4 p-4 border border-yellow-200 bg-yellow-50 rounded-md">
                    <h3 class="text-sm font-medium text-yellow-800 mb-2">Ganti Password (opsional)</h3>
                    <p class="text-xs text-yellow-600 mb-3">Kosongkan jika tidak ingin mengubah password.</p>

                    <div class="mb-3">
                        <label for="password" class="block text-sm font-medium text-gray-700">Password Baru</label>
                        <input id="password" type="password" name="password" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        @error('password')<p class="text-red-500 text-sm mt-1">{{ $message }}</p>@enderror
                    </div>

                    <div>
                        <label for="password_confirmation" class="block text-sm font-medium text-gray-700">Konfirmasi Password Baru</label>
                        <input id="password_confirmation" type="password" name="password_confirmation" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    </div>
                </div>

                <div class="flex items-center justify-end gap-3">
                    <a href="{{ route('admin.user.index') }}" class="px-4 py-2 bg-gray-200 text-gray-700 rounded-md hover:bg-gray-300 text-sm font-medium">Batal</a>
                    <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 text-sm font-medium">Simpan Perubahan</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
