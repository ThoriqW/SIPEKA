@extends('layouts.admin')

@section('content')
<div class="py-6">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="mb-6">
            <h1 class="text-2xl font-semibold text-gray-900">Edit Jabatan</h1>
            <p class="text-sm text-gray-500 mt-1"><a href="{{ route('admin.jabatan.index') }}" class="hover:text-gray-700">Jabatan</a> / Edit</p>
        </div>
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6" x-data="jabatanForm('{{ old('jenis_jabatan', $jabatan->jenis_jabatan) }}', '{{ old('jenjang', $jabatan->jenjang) }}')" x-init="initInduk(); onJenisChange(initialJenis, initialJenjang)">
            <form action="{{ route('admin.jabatan.update', $jabatan) }}" method="POST">
                @csrf @method('PUT')
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Nama Jabatan</label>
                        <input type="text" name="nama_jabatan" value="{{ old('nama_jabatan', $jabatan->nama_jabatan) }}" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 @error('nama_jabatan') border-red-500 @enderror">
                        @error('nama_jabatan')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Jenis Jabatan</label>
                        <select name="jenis_jabatan" x-on:change="onJenisChange($el.value)" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                            @foreach($jenisJabatanList as $val => $label)<option value="{{ $val }}" {{ old('jenis_jabatan', $jabatan->jenis_jabatan) == $val ? 'selected' : '' }}>{{ $label }}</option>@endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Kelas Jabatan</label>
                        <input type="number" name="kelas_jabatan" value="{{ old('kelas_jabatan', $jabatan->kelas_jabatan) }}" min="1" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    </div>
                    <div x-show="selectedJenis !== 'Pelaksana'">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Jenjang</label>
                        <select name="jenjang" x-ref="jenjangSelect" x-on:change="selectedJenjang = $el.value" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 @error('jenjang') border-red-500 @enderror">
                            <option value="">-- Pilih Jenis Jabatan dulu --</option>
                        </select>
                        @error('jenjang')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                    </div>
                    <div x-show="selectedJenis === 'Fungsional' || selectedJenis === 'Pelaksana'">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Kebutuhan</label>
                        <input type="number" name="kebutuhan" value="{{ old('kebutuhan', $jabatan->kebutuhan) }}" min="0" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">OPD</label>
                        <select name="opd_id" x-on:change="filterInduk($el.value)" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                            @foreach($opdList as $id => $nama)<option value="{{ $id }}" {{ old('opd_id', $jabatan->opd_id) == $id ? 'selected' : '' }}>{{ $nama }}</option>@endforeach
                        </select>
                    </div>
                    <div x-show="!(selectedJenis === 'Struktural' && selectedJenjang === 'Pimpinan Tinggi Pratama')">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Unit Organisasi</label>
                        <select name="induk_jabatan_id" x-ref="indukSelect" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                            <option value="">-- Tidak Ada --</option>
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

@section('scripts')
<script>
function jabatanForm(initialJenis, initialJenjang) {
    var options = {!! $jenjangOptions !!};
    var indukByOpd = {!! $indukByOpd !!};
    var selectedInduk = '{{ old('induk_jabatan_id', $jabatan->induk_jabatan_id ?? '') }}';
    return {
        initialJenis: initialJenis,
        initialJenjang: initialJenjang,
        selectedJenis: '',
        selectedJenjang: initialJenjang || '',
        onJenisChange(jenis, preSelectJenjang) {
            this.selectedJenis = jenis;
            this.selectedJenjang = preSelectJenjang || '';
            var select = this.$refs.jenjangSelect;
            select.innerHTML = '<option value="">-- Pilih Jenjang --</option>';
            if (!jenis || !options[jenis]) return;
            Object.entries(options[jenis]).forEach(function(_a) {
                var _b = _a[0], val = _b, label = _a[1];
                var opt = document.createElement('option');
                opt.value = val;
                opt.textContent = label;
                if (preSelectJenjang && val === preSelectJenjang) opt.selected = true;
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
