@extends('layouts.admin')

@section('content')
<div class="py-6">
    <div class="max-w-2xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="mb-6">
            <h1 class="text-2xl font-semibold text-gray-900">Tambah User</h1>
            <p class="text-sm text-gray-500 mt-1">Daftarkan user baru untuk login ke sistem</p>
        </div>

        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
            <form method="POST" action="{{ route('admin.user.store') }}" x-data="{ role: '{{ old('role', 'user') }}' }">
                @csrf

                <!-- Role -->
                <div class="mb-4">
                    <label for="role" class="block text-sm font-medium text-gray-700">Role</label>
                    <select id="role" name="role" x-model="role" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        <option value="user">User (Biasa)</option>
                        <option value="bkd">Super Admin (BKD)</option>
                    </select>
                    @error('role')<p class="text-red-500 text-sm mt-1">{{ $message }}</p>@enderror
                </div>

                <!-- Untuk User Biasa: Pilih Pegawai (NIP) -->
                <div x-show="role === 'user'" class="mb-4">
                    <label for="nip" class="block text-sm font-medium text-gray-700">Pegawai (NIP - Nama)</label>
                    <select id="nip" name="nip" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        <option value="">-- Pilih Pegawai --</option>
                        @foreach($pegawaiList as $p)
                            <option value="{{ $p->nip }}" {{ old('nip') == $p->nip ? 'selected' : '' }}>
                                {{ $p->nip }} — {{ $p->nama }} ({{ $p->opd->nama_opd ?? 'Tanpa OPD' }})
                            </option>
                        @endforeach
                    </select>
                    @error('nip')<p class="text-red-500 text-sm mt-1">{{ $message }}</p>@enderror
                </div>

                <!-- Untuk Super Admin: Username -->
                <div x-show="role === 'bkd'" class="mb-4">
                    <label for="username" class="block text-sm font-medium text-gray-700">Username</label>
                    <input id="username" type="text" name="username" value="{{ old('username') }}" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500" placeholder="Username untuk login">
                    @error('username')<p class="text-red-500 text-sm mt-1">{{ $message }}</p>@enderror
                </div>

                <!-- Password -->
                <div class="mb-4">
                    <label for="password" class="block text-sm font-medium text-gray-700">Password</label>
                    <input id="password" type="password" name="password" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    @error('password')<p class="text-red-500 text-sm mt-1">{{ $message }}</p>@enderror
                </div>

                <!-- Konfirmasi Password -->
                <div class="mb-6">
                    <label for="password_confirmation" class="block text-sm font-medium text-gray-700">Konfirmasi Password</label>
                    <input id="password_confirmation" type="password" name="password_confirmation" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                </div>

                <div class="flex items-center justify-end gap-3">
                    <a href="{{ route('admin.user.index') }}" class="px-4 py-2 bg-gray-200 text-gray-700 rounded-md hover:bg-gray-300 text-sm font-medium">Batal</a>
                    <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 text-sm font-medium">Simpan</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
