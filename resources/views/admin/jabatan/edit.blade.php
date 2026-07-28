@extends('layouts.admin')

@section('content')
<div class="py-6">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="mb-6">
            <h1 class="text-2xl font-semibold text-gray-900">Edit Jabatan</h1>
            <p class="text-sm text-gray-500 mt-1"><a href="{{ route('admin.jabatan.index') }}" class="hover:text-gray-700">Jabatan</a> / Edit</p>
        </div>
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6"
             x-data="jabatanForm('{{ old('jenis_jabatan', $jabatan->jenis_jabatan) }}', '{{ old('nama_jabatan', $jabatan->nama_jabatan) }}')"
             x-init="onJenisChange(initialJenis, '', initialNama)">
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

                    {{-- Nama Jabatan (dari Master) --}}
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Nama Jabatan <span class="text-red-500">*</span></label>
                        <select x-ref="namaJabatanSelect" x-on:change="onParentChange($el)"
                                class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 @error('nama_jabatan') border-red-500 @enderror">
                            <option value="">-- Pilih Jenis Jabatan dulu --</option>
                        </select>
                        @error('nama_jabatan')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                    </div>

                    {{-- Sub Jabatan --}}
                    <div x-show="hasChildren" x-cloak>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Sub Jabatan <span class="text-red-500">*</span></label>
                        <select x-ref="subJabatanSelect" x-on:change="onSubChange($el)"
                                class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                            <option value="">-- Pilih Sub Jabatan --</option>
                        </select>
                    </div>

                    {{-- Kelas Jabatan --}}
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Kelas Jabatan</label>
                        <input type="number" name="kelas_jabatan" value="{{ old('kelas_jabatan', $jabatan->kelas_jabatan) }}" min="1"
                               class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 @error('kelas_jabatan') border-red-500 @enderror">
                        @error('kelas_jabatan')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                    </div>

                    {{-- Jenjang --}}
                    <div x-show="selectedJenis !== 'Pelaksana'">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Jenjang</label>
                        <select name="jenjang" x-ref="jenjangSelect"
                                class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 @error('jenjang') border-red-500 @enderror">
                            <option value="">-- Pilih Jenis Jabatan dulu --</option>
                        </select>
                        @error('jenjang')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                    </div>

                    {{-- OPD --}}
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">OPD</label>
                        <select name="opd_id"
                                class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 @error('opd_id') border-red-500 @enderror">
                            <option value="">-- Pilih --</option>
                            @foreach($opdList as $id => $nama)
                                <option value="{{ $id }}" {{ old('opd_id', $jabatan->opd_id) == $id ? 'selected' : '' }}>{{ $nama }}</option>
                            @endforeach
                        </select>
                        @error('opd_id')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
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
function jabatanForm(initialJenis, initialNama) {
    var options = @json($jenjangOptions);
    var masterData = @json($masterJabatanData);
    var currentChildren = [];

    return {
        selectedJenis: initialJenis || '',
        hasChildren: false,
        initialJenis: initialJenis,
        initialNama: initialNama,

        onJenisChange(jenis, _, preNama) {
            this.selectedJenis = jenis;
            this.hasChildren = false;
            currentChildren = [];

            var pn = preNama || '';
            var parentName = pn.split(' - ')[0] || '';
            var subName = pn.split(' - ').slice(1).join(' - ') || '';

            var js = this.$refs.jenjangSelect;
            js.innerHTML = '<option value="">-- Pilih Jenjang --</option>';
            if (jenis && options[jenis]) {
                Object.entries(options[jenis]).forEach(function(e) {
                    var opt = document.createElement('option');
                    opt.value = e[0]; opt.textContent = e[1];
                    if ('{{ old('jenjang', $jabatan->jenjang) }}' === e[0]) opt.selected = true;
                    js.appendChild(opt);
                });
            }

            this.populateParentSelect(jenis, parentName, subName);
            this.updateHidden();
        },

        populateParentSelect(jenis, preSelectParent, preSelectSub) {
            var select = this.$refs.namaJabatanSelect;
            select.innerHTML = '<option value="">-- Pilih dari Master --</option>';
            if (!jenis || !masterData[jenis]) return;

            var self = this;
            var ps = preSelectParent || '';
            var ss = preSelectSub || '';

            masterData[jenis].forEach(function(item) {
                var opt = document.createElement('option');
                opt.value = item.nama;
                opt.textContent = item.nama;
                opt.setAttribute('data-has-children', (item.children && item.children.length > 0) ? '1' : '0');
                opt.setAttribute('data-children', item.children ? JSON.stringify(item.children) : '[]');
                if (ps && item.nama === ps) opt.selected = true;
                select.appendChild(opt);

                if (ps && item.nama === ps && item.children && item.children.length > 0) {
                    currentChildren = item.children;
                    self.hasChildren = true;
                    var subSel = self.$refs.subJabatanSelect;
                    subSel.innerHTML = '<option value="">-- Pilih Sub Jabatan --</option>';
                    item.children.forEach(function(child) {
                        var co = document.createElement('option');
                        co.value = child.nama;
                        co.textContent = child.nama;
                        if (ss && child.nama === ss) co.selected = true;
                        subSel.appendChild(co);
                    });
                }
            });
        },

        onParentChange(selectEl) {
            this.hasChildren = false;
            currentChildren = [];
            if (!selectEl.value) { this.updateHidden(); return; }

            var opt = selectEl.options[selectEl.selectedIndex];
            var hasChildren = opt.getAttribute('data-has-children') === '1';
            var childrenJson = opt.getAttribute('data-children') || '[]';

            if (hasChildren) {
                currentChildren = JSON.parse(childrenJson);
                this.hasChildren = true;

                var subSelect = this.$refs.subJabatanSelect;
                subSelect.innerHTML = '<option value="">-- Pilih Sub Jabatan --</option>';
                currentChildren.forEach(function(child) {
                    var co = document.createElement('option');
                    co.value = child.nama;
                    co.textContent = child.nama;
                    subSelect.appendChild(co);
                });
            }
            this.updateHidden();
        },

        onSubChange() {
            this.updateHidden();
        },

        updateHidden() {
            var parentName = this.$refs.namaJabatanSelect.value || '';
            if (this.hasChildren && this.$refs.subJabatanSelect) {
                var subName = this.$refs.subJabatanSelect.value;
                this.$refs.namaJabatanHidden.value = subName ? parentName + ' - ' + subName : '';
            } else {
                this.$refs.namaJabatanHidden.value = parentName;
            }
        }
    }
}
</script>
@append
