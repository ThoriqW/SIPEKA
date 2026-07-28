@extends('layouts.admin')

@section('content')
<div class="py-6">
    <div class="max-w-2xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="mb-6">
            <h1 class="text-2xl font-semibold text-gray-900">Edit Tugas Tambahan</h1>
            <p class="text-sm text-gray-500 mt-1"><a href="{{ route('admin.tugas-tambahan.index') }}" class="hover:text-gray-700">Master Tugas Tambahan</a> / {{ $tugasTambahan->nama_tugas }}</p>
        </div>
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
            <form method="POST" action="{{ route('admin.tugas-tambahan.update', $tugasTambahan) }}">
                @csrf @method('PUT')
                <div class="mb-4">
                    <label for="nama_tugas" class="block text-sm font-medium text-gray-700">Nama Tugas</label>
                    <input type="text" name="nama_tugas" value="{{ old('nama_tugas', $tugasTambahan->nama_tugas) }}"
                           class="mt-1 w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 @error('nama_tugas') border-red-500 @enderror">
                    @error('nama_tugas')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                </div>
                <div class="flex items-center gap-3">
                    <a href="{{ route('admin.tugas-tambahan.index') }}" class="px-4 py-2 bg-gray-200 rounded-md text-sm">Kembali</a>
                    <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 text-sm">Perbarui</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
