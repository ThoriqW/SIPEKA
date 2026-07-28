@extends('layouts.admin')

@section('content')
<div class="py-6">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="mb-6">
            <h1 class="text-2xl font-semibold text-gray-900">Tambah UNOR</h1>
            <p class="text-sm text-gray-500 mt-1"><a href="{{ route('admin.opd.index') }}" class="hover:text-gray-700">UNOR</a> / Tambah</p>
        </div>

        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6 max-w-2xl">
            <form method="POST" action="{{ route('admin.opd.store') }}">
                @csrf

                <div class="mb-4">
                    <label for="nama_unor" class="block text-sm font-medium text-gray-700">Nama UNOR</label>
                    <input type="text" name="nama_unor" id="nama_unor"
                           value="{{ old('nama_unor') }}"
                           class="mt-1 w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 @error('nama_unor') border-red-500 @enderror">
                    @error('nama_unor')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                </div>

                <div class="mb-4">
                    <label for="kode_unor" class="block text-sm font-medium text-gray-700">Kode UNOR</label>
                    <input type="text" name="kode_unor" id="kode_unor"
                           value="{{ old('kode_unor') }}"
                           class="mt-1 w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 @error('kode_unor') border-red-500 @enderror">
                    @error('kode_unor')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                </div>

                <div class="mb-4">
                    <label for="singkatan" class="block text-sm font-medium text-gray-700">Singkatan</label>
                    <input type="text" name="singkatan" id="singkatan"
                           value="{{ old('singkatan') }}" maxlength="10"
                           class="mt-1 w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 @error('singkatan') border-red-500 @enderror">
                    @error('singkatan')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                </div>

                <div class="mb-6">
                    <label for="parent_id" class="block text-sm font-medium text-gray-700">Induk UNOR</label>
                    <select name="parent_id" id="parent_id"
                            class="mt-1 w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 @error('parent_id') border-red-500 @enderror">
                        <option value="">-- Tanpa Induk (Root) --</option>
                        @foreach($parentList as $id => $nama)
                        <option value="{{ $id }}" {{ old('parent_id') == $id ? 'selected' : '' }}>{{ $nama }}</option>
                        @endforeach
                    </select>
                    @error('parent_id')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                </div>

                <div class="flex items-center gap-3">
                    <a href="{{ route('admin.opd.index') }}" class="inline-flex items-center px-4 py-2 bg-gray-200 border border-transparent rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest hover:bg-gray-300 focus:outline-none transition">
                        Kembali
                    </a>
                    <button type="submit" class="inline-flex items-center px-4 py-2 bg-blue-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-blue-700 focus:outline-none transition">
                        Simpan
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
