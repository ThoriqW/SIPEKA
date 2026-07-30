@extends('layouts.admin')

@section('content')
<div class="py-6">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="mb-6">
            <h1 class="text-2xl font-semibold text-gray-900">Edit Jabatan</h1>
            <p class="text-sm text-gray-500 mt-1"><a href="{{ route('admin.jabatan.index') }}" class="hover:text-gray-700">Jabatan</a> / Edit</p>
        </div>
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6"
             x-data="jabatanForm()"
             x-init="init('{{ old('jenis_jabatan', $jabatan->jenis_jabatan) }}', '{{ old('nama_jabatan', $jabatan->nama_jabatan) }}', '{{ $currentIndukId }}', '{{ $currentUnitId }}')">
            <form action="{{ route('admin.jabatan.update', $jabatan) }}" method="POST">
                @csrf @method('PUT')
                <input type="hidden" name="nama_jabatan" x-ref="namaJabatanHidden"
                       value="{{ old('nama_jabatan', $jabatan->nama_jabatan) }}">

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    {{-- Jenis Jabatan --}}
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Jenis Jabatan</label>
                        <select name="jenis_jabatan" x-on:change="onJenisChange($el.value)"
                                class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 @error('jenis_jabatan') border-red-500 @enderror">
                            <option value="">-- Pilih --</option>
                            @foreach($jenisJabatanList as $val => $label)
                                <option value="{{ $val }}" {{ old('jenis_jabatan', $jabatan->jenis_jabatan) == $val ? 'selected' : '' }}>{{ $label }}</option>
                            @endforeach
                        </select>
                        @error('jenis_jabatan')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                    </div>

                    {{-- Nama Jabatan — searchable + validasi blur --}}
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Nama Jabatan <span class="text-red-500">*</span></label>
                        <div class="relative">
                            <div class="relative">
                                <input type="text" x-model="namaSearch" x-ref="namaSearch"
                                       @focus="namaOpen = true" @click="namaOpen = true" @input="namaOpen = true"
                                       @blur="$nextTick(() => { namaOpen = false; if (namaSearch && namaSearch !== namaSelected) namaSearch = '' })"
                                       :placeholder="namaSelected || 'Cari nama jabatan...'"
                                       class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 pr-8 @error('nama_jabatan') border-red-500 @enderror">
                                <span class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 pointer-events-none"
                                      @click.stop="$refs.namaSearch.focus(); namaOpen = true">▾</span>
                            </div>
                            <div x-show="namaOpen && filteredNamaList.length > 0" x-cloak @mousedown.prevent
                                 class="absolute z-50 mt-1 w-full bg-white border border-gray-300 rounded-md shadow-lg max-h-48 overflow-y-auto">
                                <template x-for="item in filteredNamaList" :key="item.id || item.nama">
                                    <div @click="selectNama(item)"
                                         class="px-3 py-2 text-sm hover:bg-blue-50 cursor-pointer">
                                        <span x-text="item.nama"></span>
                                        <span x-show="item.children && item.children.length > 0"
                                              class="ml-1 text-xs text-gray-400"
                                              x-text="'(' + item.children.length + ')'"></span>
                                    </div>
                                </template>
                            </div>
                        </div>
                        @error('nama_jabatan')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                    </div>

                    {{-- Sub Jabatan — searchable + validasi blur --}}
                    <div x-show="hasChildren" x-cloak>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Sub Jabatan <span class="text-red-500">*</span></label>
                        <div class="relative">
                            <div class="relative">
                                <input type="text" x-model="subSearch" x-ref="subSearch"
                                       @focus="subOpen = true" @click="subOpen = true" @input="subOpen = true"
                                       @blur="$nextTick(() => { subOpen = false; if (subSearch && subSearch !== subSelected) subSearch = '' })"
                                       :placeholder="subSelected || 'Cari sub jabatan...'"
                                       class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 pr-8">
                                <span class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 pointer-events-none"
                                      @click.stop="$refs.subSearch.focus(); subOpen = true">▾</span>
                            </div>
                            <div x-show="subOpen && filteredSubList.length > 0" x-cloak @mousedown.prevent
                                 class="absolute z-50 mt-1 w-full bg-white border border-gray-300 rounded-md shadow-lg max-h-48 overflow-y-auto">
                                <template x-for="item in filteredSubList" :key="item.nama">
                                    <div @click="selectSub(item)"
                                         class="px-3 py-2 text-sm hover:bg-blue-50 cursor-pointer">
                                        <span x-text="item.nama"></span>
                                    </div>
                                </template>
                            </div>
                        </div>
                    </div>

                    {{-- Kelas Jabatan --}}
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Kelas Jabatan</label>
                        <input type="number" name="kelas_jabatan" value="{{ old('kelas_jabatan', $jabatan->kelas_jabatan) }}" min="1"
                               class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 @error('kelas_jabatan') border-red-500 @enderror">
                        @error('kelas_jabatan')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                    </div>

                    {{-- Jenjang — regular select --}}
                    <div x-show="selectedJenis !== 'Pelaksana' && jenjangList.length > 0">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Jenjang</label>
                        <select name="jenjang"                                 class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 @error('jenjang') border-red-500 @enderror">
                            <option value="">-- Pilih --</option>
                            <template x-for="item in jenjangList" :key="item.id">
                                <option :value="item.nama" x-text="item.nama"
                                        :selected="item.nama === '{{ old('jenjang', $jabatan->jenjang) }}'"></option>
                            </template>
                        </select>
                        @error('jenjang')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                    </div>

                    {{-- Kebutuhan --}}
                    <div x-show="selectedJenis === 'Fungsional' || selectedJenis === 'Pelaksana'" x-cloak>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Kebutuhan</label>
                        <input type="number" name="kebutuhan" value="{{ old('kebutuhan', $kebutuhan) }}" min="0"
                               class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 @error('kebutuhan') border-red-500 @enderror">
                        @error('kebutuhan')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                    </div>

                    {{-- Unor Induk — regular select --}}
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Unit Organisasi Induk</label>
                        <select x-on:change="onIndukChange($el.value)"
                                class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 @error('unor_id') border-red-500 @enderror">
                            <option value="">-- Pilih Unit Organisasi Induk --</option>
                            @foreach($indukList as $id => $nama)
                                <option value="{{ $id }}" {{ old('unor_id', $currentIndukId) == $id ? 'selected' : '' }}>{{ $nama }}</option>
                            @endforeach
                        </select>
                        @error('unor_id')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                    </div>

                    {{-- Unit Organisasi — regular select --}}
                    <div x-show="unitList.length > 0" x-cloak>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Unit Organisasi</label>
                        <select name="unor_id" x-ref="unitSelect"
                                class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 @error('unor_id') border-red-500 @enderror">
                            <option value="">-- Pilih Unit Organisasi --</option>
                            <template x-for="u in unitList" :key="u.id">
                                <option :value="u.id" x-text="u.nama"
                                        :selected="u.id == '{{ $currentUnitId }}'"></option>
                            </template>
                        </select>
                        @error('unor_id')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
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
function jabatanForm() {
    var options = @json($jenjangOptions);
    var referensiData = @json($referensiJabatanData);
    var unorData = @json($unorByInduk);

    return {
        selectedJenis: '',
        hasChildren: false,
        unitList: [],
        namaJabatanList: [], subJabatanList: [], jenjangList: [],

        namaSearch: '', namaOpen: false, namaSelected: '',
        subSearch: '', subOpen: false, subSelected: '',

        get filteredNamaList() { return this.filterList(this.namaJabatanList, this.namaSearch); },
        get filteredSubList() { return this.filterList(this.subJabatanList, this.subSearch); },

        filterList: function(list, s) {
            if (!s) return list;
            var q = s.toLowerCase();
            return list.filter(function(i) { return (i.nama || '').toLowerCase().includes(q); });
        },

        init: function(jenis, preNama, indukId, unitId) {
            if (jenis) this.onJenisChange(jenis, preNama);
            if (indukId) {
                this.unitList = unorData[indukId] || [];
                if (unitId) {
                    this.$nextTick(function() {
                        var sel = document.querySelector('[x-ref="unitSelect"]');
                        if (sel) sel.value = unitId;
                    });
                }
            }
        },

        onJenisChange: function(jenis, preNama) {
            this.selectedJenis = jenis;
            this.hasChildren = false;
            this.subJabatanList = [];
            this.subSearch = ''; this.subSelected = '';

            var pn = preNama || '';
            var parentName = pn.split(' - ')[0] || '';
            var subName = pn.split(' - ').slice(1).join(' - ') || '';

            this.jenjangList = [];
            if (jenis && options[jenis]) {
                this.jenjangList = Object.entries(options[jenis]).map(function(e) {
                    return {id: e[0], nama: e[1]};
                });
            }

            this.namaJabatanList = [];
            if (jenis && referensiData[jenis]) {
                this.namaJabatanList = referensiData[jenis].map(function(item) {
                    return {id: item.id, nama: item.nama, children: item.children || []};
                });
            }

            if (parentName) this.selectNamaByName(parentName, subName);
            this.updateHidden();
        },

        selectNamaByName: function(name, subName) {
            this.namaSearch = ''; this.namaSelected = ''; this.namaOpen = false;
            var match = this.namaJabatanList.find(function(i) { return i.nama === name; });
            if (match) {
                this.namaSelected = match.nama;
                if (match.children && match.children.length > 0) {
                    this.hasChildren = true;
                    this.subJabatanList = match.children;
                    if (subName) this.subSelected = subName;
                }
            }
        },

        selectNama: function(item) {
            this.namaOpen = false; this.namaSearch = ''; this.namaSelected = item.nama;
            this.hasChildren = false;
            this.subJabatanList = [];
            this.subSearch = ''; this.subSelected = '';
            if (item.children && item.children.length > 0) {
                this.hasChildren = true;
                this.subJabatanList = item.children;
            }
            this.updateHidden();
        },

        selectSub: function(item) {
            this.subOpen = false; this.subSearch = ''; this.subSelected = item.nama;
            this.updateHidden();
        },

        onIndukChange: function(indukId) {
            this.unitList = unorData[indukId] || [];
        },

        updateHidden: function() {
            if (this.hasChildren && this.subSelected) {
                this.$refs.namaJabatanHidden.value = this.namaSelected + ' - ' + this.subSelected;
            } else {
                this.$refs.namaJabatanHidden.value = this.namaSelected;
            }
        }
    };
}
</script>
@append
