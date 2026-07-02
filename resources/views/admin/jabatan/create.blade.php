@extends('layouts.admin')

@section('content')
<div class="py-6">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="mb-6">
            <h1 class="text-2xl font-semibold text-gray-900">Tambah Jabatan</h1>
            <p class="text-sm text-gray-500 mt-1"><a href="{{ route('admin.jabatan.index') }}" class="hover:text-gray-700">Jabatan</a> / Tambah</p>
        </div>
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6" x-data="jabatanForm()" x-init="initInduk(); onJenisChange('{{ old('jenis_jabatan', '') }}')">
            <form action="{{ route('admin.jabatan.store') }}" method="POST">
                @csrf
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Nama Jabatan</label>
                        <input type="text" name="nama_jabatan" value="{{ old('nama_jabatan') }}" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 @error('nama_jabatan') border-red-500 @enderror">
                        @error('nama_jabatan')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Kode Jabatan</label>
                        <input type="text" name="kode_jabatan" value="{{ old('kode_jabatan') }}" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 @error('kode_jabatan') border-red-500 @enderror">
                        @error('kode_jabatan')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Jenis Jabatan</label>
                        <select name="jenis_jabatan" x-on:change="onJenisChange($el.value)" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 @error('jenis_jabatan') border-red-500 @enderror">
                            <option value="">-- Pilih --</option>
                            @foreach($jenisJabatanList as $val => $label)<option value="{{ $val }}" {{ old('jenis_jabatan') == $val ? 'selected' : '' }}>{{ $label }}</option>@endforeach
                        </select>
                        @error('jenis_jabatan')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Kelas Jabatan</label>
                        <input type="number" name="kelas_jabatan" value="{{ old('kelas_jabatan') }}" min="1" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 @error('kelas_jabatan') border-red-500 @enderror">
                        @error('kelas_jabatan')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Jenjang</label>
                        <select name="jenjang" x-ref="jenjangSelect" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 @error('jenjang') border-red-500 @enderror">
                            <option value="">-- Pilih Jenis Jabatan dulu --</option>
                        </select>
                        @error('jenjang')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                    </div>
                    <div x-show="selectedJenis === 'Fungsional' || selectedJenis === 'Pelaksana'">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Kebutuhan</label>
                        <input type="number" name="kebutuhan" value="{{ old('kebutuhan') }}" min="0" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 @error('kebutuhan') border-red-500 @enderror">
                        @error('kebutuhan')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">OPD</label>
                        <select name="opd_id" x-on:change="filterInduk($el.value)" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 @error('opd_id') border-red-500 @enderror">
                            <option value="">-- Pilih --</option>
                            @foreach($opdList as $id => $nama)<option value="{{ $id }}" {{ old('opd_id') == $id ? 'selected' : '' }}>{{ $nama }}</option>@endforeach
                        </select>
                        @error('opd_id')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Induk Jabatan</label>
                        <select name="induk_jabatan_id" x-ref="indukSelect" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                            <option value="">-- Tidak Ada --</option>
                        </select>
                    </div>
                </div>
                <div class="flex gap-3 mt-6">
                    <a href="{{ route('admin.jabatan.index') }}" class="px-4 py-2 bg-gray-200 text-gray-700 rounded-md hover:bg-gray-300 text-sm">Kembali</a>
                    <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 text-sm">Simpan</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
function jabatanForm() {
    var options = {!! $jenjangOptions !!};
    var indukByOpd = {!! $indukByOpd !!};
    var selectedInduk = '{{ old('induk_jabatan_id', '') }}';
    return {
        selectedJenis: '',
        onJenisChange(jenis) {
            this.selectedJenis = jenis;
            var select = this.$refs.jenjangSelect;
            select.innerHTML = '<option value="">-- Pilih Jenjang --</option>';
            if (!jenis || !options[jenis]) return;
            Object.entries(options[jenis]).forEach(function(_a) {
                var _b = _a[0], val = _b, label = _a[1];
                var opt = document.createElement('option');
                opt.value = val;
                opt.textContent = label;
                select.appendChild(opt);
            });
        },
        filterInduk(opdId) {
            var select = this.$refs.indukSelect;
            select.innerHTML = '<option value="">-- Tidak Ada --</option>';
            var items = indukByOpd[opdId] || [];
            items.forEach(function(item) {
                var opt = document.createElement('option');
                opt.value = item.id;
                opt.textContent = item.nama;
                if (String(item.id) === String(selectedInduk)) opt.selected = true;
                select.appendChild(opt);
            });
        },
        initInduk() {
            var opdSelect = this.$el.querySelector('[name="opd_id"]');
            if (opdSelect && opdSelect.value) {
                this.filterInduk(opdSelect.value);
            }
        }
    }
}
</script>
@append
