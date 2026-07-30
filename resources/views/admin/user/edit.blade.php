@extends('layouts.admin')

@section('content')
<div class="py-6">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="mb-6">
            <h1 class="text-2xl font-semibold text-gray-900">Edit User</h1>
            <p class="text-sm text-gray-500 mt-1"><a href="{{ route('admin.user.index') }}" class="hover:text-gray-700">User</a> / Edit</p>
        </div>

        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
            @php $isSelf = $user->id === auth()->id(); @endphp

            <form method="POST" action="{{ route('admin.user.update', $user) }}">
                @csrf
                @method('PUT')

                <!-- Info Pegawai (read-only) -->
                <div class="mb-6 p-4 bg-gray-50 rounded-md">
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
                            <td class="font-medium">{{ $pegawai->penempatanAktif->unor->nama_unor ?? '—' }}</td>
                        </tr>
                    </table>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Role -->
                    <div>
                        <label for="role" class="block text-sm font-medium text-gray-700 mb-1">Role</label>
                        @if($isSelf)
                            <input type="text" value="{{ old('role', $user->role) === 'admin' ? 'Admin' : 'User' }}" readonly class="w-full rounded-md border-gray-300 bg-gray-100 shadow-sm text-gray-600 cursor-not-allowed">
                            <p class="mt-1 text-xs text-gray-400">Anda tidak dapat mengubah role sendiri.</p>
                        @else
                            <select id="role" name="role" required class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 @error('role') border-red-500 @enderror">
                                <option value="user" {{ old('role', $user->role) == 'user' ? 'selected' : '' }}>User</option>
                                <option value="admin" {{ old('role', $user->role) == 'admin' ? 'selected' : '' }}>Admin</option>
                            </select>
                            @error('role')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                        @endif
                    </div>

                    <!-- Status Aktif -->
                    <div>
                        <label for="is_active" class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                        @if($isSelf)
                            <input type="text" value="{{ old('is_active', $user->is_active) == 1 ? 'Aktif' : 'Nonaktif' }}" readonly class="w-full rounded-md border-gray-300 bg-gray-100 shadow-sm text-gray-600 cursor-not-allowed">
                            <p class="mt-1 text-xs text-gray-400">Anda tidak dapat mengubah status sendiri.</p>
                        @else
                            <select id="is_active" name="is_active" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 @error('is_active') border-red-500 @enderror">
                                <option value="1" {{ old('is_active', $user->is_active) == 1 ? 'selected' : '' }}>Aktif</option>
                                <option value="0" {{ old('is_active', $user->is_active) == 0 ? 'selected' : '' }}>Nonaktif</option>
                            </select>
                            @error('is_active')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                        @endif
                    </div>
                </div>

                <!-- Password Baru (opsional) -->
                <div class="mt-6 p-4 border border-yellow-200 bg-yellow-50 rounded-md">
                    <h3 class="text-sm font-medium text-yellow-800 mb-2">Ganti Password (opsional)</h3>
                    <p class="text-xs text-yellow-600 mb-3">Kosongkan jika tidak ingin mengubah password.</p>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label for="password" class="block text-sm font-medium text-gray-700 mb-1">Password Baru</label>
                            <input id="password" type="password" name="password" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 @error('password') border-red-500 @enderror">
                            @error('password')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                        </div>

                        <div>
                            <label for="password_confirmation" class="block text-sm font-medium text-gray-700 mb-1">Konfirmasi Password Baru</label>
                            <input id="password_confirmation" type="password" name="password_confirmation" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        </div>
                    </div>
                </div>

                <div class="flex gap-3 mt-6">
                    <a href="{{ route('admin.user.index') }}" class="px-4 py-2 bg-gray-200 text-gray-700 rounded-md hover:bg-gray-300 text-sm">Kembali</a>
                    <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 text-sm">Simpan Perubahan</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
