@extends('layouts.admin')

@section('content')
<div class="py-6">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="mb-6">
            <h1 class="text-2xl font-semibold text-gray-900">Edit Jabatan</h1>
            <p class="text-sm text-gray-500 mt-1"><a href="{{ route('admin.jabatan.index') }}" class="hover:text-gray-700">Jabatan</a> / Edit</p>
        </div>
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6" x-data="{ jenis: '{{ old('jenis_jabatan', $jabatan->jenis_jabatan) }}' }">
            <form action="{{ route('admin.jabatan.update', $jabatan) }}" method="POST">
                @csrf @method('PUT')
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Nama Jabatan</label>
                        <input type="text" name="nama_jabatan" value="{{ old('nama_jabatan', $jabatan->nama_jabatan) }}" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 @error('nama_jabatan') border-red-500 @enderror">
                        @error('nama_jabatan')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Kode Jabatan</label>
                        <input type="text" name="kode_jabatan" value="{{ old('kode_jabatan', $jabatan->kode_jabatan) }}" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 @error('kode_jabatan') border-red-500 @enderror">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Jenis Jabatan</label>
                        <select name="jenis_jabatan" x-model="jenis" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                            @foreach($jenisJabatanList as $val => $label)<option value="{{ $val }}" {{ old('jenis_jabatan', $jabatan->jenis_jabatan) == $val ? 'selected' : '' }}>{{ $label }}</option>@endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Kelas Jabatan</label>
                        <input type="number" name="kelas_jabatan" value="{{ old('kelas_jabatan', $jabatan->kelas_jabatan) }}" min="1" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    </div>
                    <div x-show="jenis === 'Fungsional' || jenis === 'Pelaksana'">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Kebutuhan</label>
                        <input type="number" name="kebutuhan" value="{{ old('kebutuhan', $jabatan->kebutuhan) }}" min="0" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">OPD</label>
                        <select name="opd_id" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                            @foreach($opdList as $id => $nama)<option value="{{ $id }}" {{ old('opd_id', $jabatan->opd_id) == $id ? 'selected' : '' }}>{{ $nama }}</option>@endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Induk Jabatan</label>
                        <select name="induk_jabatan_id" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                            <option value="">-- Tidak Ada --</option>
                            @foreach($indukList as $id => $nama)<option value="{{ $id }}" {{ old('induk_jabatan_id', $jabatan->induk_jabatan_id) == $id ? 'selected' : '' }}>{{ $nama }}</option>@endforeach
                        </select>
                    </div>
                </div>
                <div class="flex gap-3 mt-6">
                    <a href="{{ route('admin.jabatan.index') }}" class="px-4 py-2 bg-gray-200 text-gray-700 rounded-md hover:bg-gray-300 text-sm">Kembali</a>
                    <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 text-sm">Perbarui</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
