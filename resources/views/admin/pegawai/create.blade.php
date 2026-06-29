@extends('layouts.admin')

@section('content')
<div class="py-6">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="mb-6">
            <h1 class="text-2xl font-semibold text-gray-900">Tambah Pegawai</h1>
            <p class="text-sm text-gray-500 mt-1"><a href="{{ route('admin.pegawai.index') }}" class="hover:text-gray-700">Pegawai</a> / Tambah</p>
        </div>
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6" x-data="{}">
            <form action="{{ route('admin.pegawai.store') }}" method="POST">
                @csrf
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">NIP (18 digit)</label>
                        <input type="text" name="nip" x-ref="nip" maxlength="18" value="{{ old('nip') }}" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 @error('nip') border-red-500 @enderror">
                        @error('nip')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                        <button type="button" x-on:click="fetch('/admin/pegawai/extract-tanggal-lahir?nip=' + $refs.nip.value).then(r => r.json()).then(d => { if(d.success) $refs.tanggal_lahir.value = d.tanggal_lahir })" class="mt-2 text-sm text-blue-600 hover:text-blue-800">Isi Otomatis Tanggal Lahir dari NIP</button>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Nama</label>
                        <input type="text" name="nama" value="{{ old('nama') }}" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 @error('nama') border-red-500 @enderror">
                        @error('nama')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Jenis Kepegawaian</label>
                        <select name="jenis_kepegawaian" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                            <option value="">-- Pilih --</option>
                            @foreach($jenisKepegawaianList as $val => $label)<option value="{{ $val }}" {{ old('jenis_kepegawaian') == $val ? 'selected' : '' }}>{{ $label }}</option>@endforeach
                        </select>
                        @error('jenis_kepegawaian')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Tanggal Lahir</label>
                        <input type="date" name="tanggal_lahir" x-ref="tanggal_lahir" value="{{ old('tanggal_lahir') }}" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 @error('tanggal_lahir') border-red-500 @enderror">
                        @error('tanggal_lahir')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Golongan/Pangkat</label>
                        <select name="golongan_pangkat" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                            <option value="">-- Pilih --</option>
                            @foreach($golonganPangkatList as $val => $label)<option value="{{ $val }}" {{ old('golongan_pangkat') == $val ? 'selected' : '' }}>{{ $label }}</option>@endforeach
                        </select>
                        @error('golongan_pangkat')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Pendidikan</label>
                        <select name="pendidikan" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                            <option value="">-- Pilih --</option>
                            @foreach($pendidikanList as $val => $label)<option value="{{ $val }}" {{ old('pendidikan') == $val ? 'selected' : '' }}>{{ $label }}</option>@endforeach
                        </select>
                        @error('pendidikan')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Jenjang</label>
                        <select name="jenjang" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                            <option value="">-- Pilih --</option>
                            @foreach($jenjangList as $val => $label)<option value="{{ $val }}" {{ old('jenjang') == $val ? 'selected' : '' }}>{{ $label }}</option>@endforeach
                        </select>
                        @error('jenjang')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">OPD</label>
                        <select name="opd_id" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                            <option value="">-- Pilih --</option>
                            @foreach($opdList as $id => $nama)<option value="{{ $id }}" {{ old('opd_id') == $id ? 'selected' : '' }}>{{ $nama }}</option>@endforeach
                        </select>
                        @error('opd_id')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Jabatan</label>
                        <select name="jabatan_id" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                            <option value="">-- Pilih --</option>
                            @foreach($jabatanList as $id => $nama)<option value="{{ $id }}" {{ old('jabatan_id') == $id ? 'selected' : '' }}>{{ $nama }}</option>@endforeach
                        </select>
                        @error('jabatan_id')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                    </div>
                </div>
                <div class="flex gap-3 mt-6">
                    <a href="{{ route('admin.pegawai.index') }}" class="px-4 py-2 bg-gray-200 text-gray-700 rounded-md hover:bg-gray-300 text-sm">Kembali</a>
                    <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 text-sm">Simpan</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
