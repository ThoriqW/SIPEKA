@extends('layouts.admin')

@section('content')
<div class="py-6">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="mb-6">
            <h1 class="text-2xl font-semibold text-gray-900">Tambah User</h1>
            <p class="text-sm text-gray-500 mt-1"><a href="{{ route('admin.user.index') }}" class="hover:text-gray-700">User</a> / Tambah</p>
        </div>

        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
            <form method="POST" action="{{ route('admin.user.store') }}" x-data="{ role: '{{ old('role', 'user') }}' }">
                @csrf

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Role -->
                    <div>
                        <label for="role" class="block text-sm font-medium text-gray-700 mb-1">Role <span class="text-red-500">*</span></label>
                        <select id="role" name="role" x-model="role" required class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 @error('role') border-red-500 @enderror">
                            <option value="">-- Pilih Role --</option>
                            <option value="user">User</option>
                            <option value="admin">Admin</option>
                        </select>
                        @error('role')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                    </div>

                    <!-- Untuk User: Pilih Pegawai (NIP) — searchable -->
                    <div x-show="role === 'user'" x-data="{ pegOpen: false, pegSearch: '', pegSelected: '{{ old('nip') ? ($pegawaiList->firstWhere('nip', old('nip'))?->nip . ' — ' . $pegawaiList->firstWhere('nip', old('nip'))?->nama ?? '') : '' }}' }" class="relative">
                        <input type="hidden" name="nip" x-ref="pegNip" value="{{ old('nip') }}">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Pegawai <span class="text-red-500">*</span></label>
                        <div class="relative">
                            <input type="text" x-model="pegSearch" x-ref="pegSearchInput"
                                   @focus="pegOpen = true" @click="pegOpen = true"
                                   @input="pegOpen = true"
                                   @blur="setTimeout(() => { pegOpen = false; if (pegSearch && pegSearch !== pegSelected) pegSearch = '' }, 150)"
                                   :placeholder="pegSelected || 'Cari NIP atau Nama Pegawai'"
                                   class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 pr-8 @error('nip') border-red-500 @enderror">
                            <span class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 pointer-events-none"
                                  @click.stop="$refs.pegSearchInput.focus(); pegOpen = true">▾</span>
                        </div>
                        <div x-show="pegOpen" x-cloak @mousedown.prevent
                             class="absolute z-50 mt-1 w-full bg-white border border-gray-300 rounded-md shadow-lg max-h-48 overflow-y-auto">
                            @foreach($pegawaiList as $p)
                            @php $label = $p->nip . ' — ' . $p->nama . ' (' . ($p->penempatanAktif->unor->nama_unor ?? 'Tanpa OPD') . ')'; @endphp
                            <div @click="pegOpen = false; pegSearch = ''; pegSelected = '{{ $label }}'; $refs.pegNip.value = '{{ $p->nip }}'"
                                 x-show="!pegSearch || '{{ strtolower($label) }}'.includes(pegSearch.toLowerCase())"
                                 class="px-3 py-2 text-sm hover:bg-blue-50 cursor-pointer {{ old('nip') == $p->nip ? 'bg-blue-100' : '' }}">{{ $label }}</div>
                            @endforeach
                        </div>
                        @error('nip')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                    </div>

                    <!-- Untuk Admin: Username -->
                    <div x-show="role === 'admin'">
                        <label for="username" class="block text-sm font-medium text-gray-700 mb-1">Username <span class="text-red-500">*</span></label>
                        <input id="username" type="text" name="username" value="{{ old('username') }}" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 @error('username') border-red-500 @enderror" placeholder="Username untuk login">
                        @error('username')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                    </div>

                    <!-- Password -->
                    <div>
                        <label for="password" class="block text-sm font-medium text-gray-700 mb-1">Password <span class="text-red-500">*</span></label>
                        <input id="password" type="password" name="password" required class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 @error('password') border-red-500 @enderror">
                        @error('password')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                    </div>

                    <!-- Konfirmasi Password -->
                    <div>
                        <label for="password_confirmation" class="block text-sm font-medium text-gray-700 mb-1">Konfirmasi Password <span class="text-red-500">*</span></label>
                        <input id="password_confirmation" type="password" name="password_confirmation" required class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 @error('password') border-red-500 @enderror">
                        @error('password')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                    </div>
                </div>

                <div class="flex gap-3 mt-6">
                    <a href="{{ route('admin.user.index') }}" class="px-4 py-2 bg-gray-200 text-gray-700 rounded-md hover:bg-gray-300 text-sm">Kembali</a>
                    <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 text-sm">Simpan</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
