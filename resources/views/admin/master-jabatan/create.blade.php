@extends('layouts.admin')

@section('content')
<div class="py-6">
    <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="mb-6">
            <h1 class="text-2xl font-semibold text-gray-900">Tambah Master Jabatan</h1>
            <p class="text-sm text-gray-500 mt-1"><a href="{{ route('admin.master-jabatan.index') }}" class="hover:text-gray-700">Master Jabatan</a> / Tambah</p>
        </div>
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
            <form action="{{ route('admin.master-jabatan.store') }}" method="POST">
                @csrf
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Nama Jabatan</label>
                        <input type="text" name="nama_jabatan" value="{{ old('nama_jabatan') }}" required
                               class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 @error('nama_jabatan') border-red-500 @enderror">
                        @error('nama_jabatan')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Jenis Jabatan</label>
                        <select name="jenis_jabatan" required onchange="document.getElementById('indukField').style.display = this.value === 'Fungsional' ? '' : 'none'"
                                class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 @error('jenis_jabatan') border-red-500 @enderror">
                            <option value="">-- Pilih --</option>
                            @foreach($jenisJabatanList as $val => $label)
                                <option value="{{ $val }}" {{ old('jenis_jabatan') == $val ? 'selected' : '' }}>{{ $label }}</option>
                            @endforeach
                        </select>
                        @error('jenis_jabatan')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                    </div>
                    <div id="indukField" style="display:none;">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Induk (untuk Sub Jabatan)</label>
                        <select name="parent_id"
                                class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 @error('parent_id') border-red-500 @enderror">
                            <option value="">-- Tidak Ada (Jabatan Utama) --</option>
                            @foreach($parentList as $id => $nama)
                                <option value="{{ $id }}" {{ old('parent_id') == $id ? 'selected' : '' }}>{{ $nama }}</option>
                            @endforeach
                        </select>
                        <p class="mt-1 text-xs text-gray-500">Pilih induk hanya jika ini adalah sub-jabatan dari jabatan lain</p>
                        @error('parent_id')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                    </div>
                </div>
                <div class="flex gap-3 mt-6">
                    <a href="{{ route('admin.master-jabatan.index') }}" class="px-4 py-2 bg-gray-200 text-gray-700 rounded-md hover:bg-gray-300 text-sm">Kembali</a>
                    <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 text-sm">Simpan</button>
                </div>
            </form>
        </div>
    </div>
</div>
<script>
// Tampilkan induk hanya untuk Fungsional
var jenisSelect = document.querySelector('[name="jenis_jabatan"]');
if (jenisSelect && jenisSelect.value === 'Fungsional') {
    document.getElementById('indukField').style.display = '';
}
</script>
@endsection
