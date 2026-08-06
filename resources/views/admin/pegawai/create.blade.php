@extends('layouts.admin')

@section('content')
<div class="py-6">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="mb-6">
            <h1 class="text-2xl font-semibold text-gray-900">Tambah Pegawai</h1>
            <p class="text-sm text-gray-500 mt-1"><a href="{{ route('admin.pegawai.index') }}" class="hover:text-gray-700">Pegawai</a> / Tambah</p>
        </div>
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6" x-data="pegawaiForm()" x-init="initGolongan('{{ old('jenis_kepegawaian', '') }}')">
            <form action="{{ route('admin.pegawai.store') }}" method="POST">
                @csrf
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">NIP (18 digit) <span class="text-red-500">*</span></label>
                        <input type="text" name="nip" x-ref="nip" maxlength="18" value="{{ old('nip') }}" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 @error('nip') border-red-500 @enderror">
                        @error('nip')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                        <div class="mt-2 flex items-center gap-2">
                            <button type="button"
                                    x-on:click="nipLoading = true; nipError = ''; nipSuccess = false; fetch('/admin/pegawai/extract-tanggal-lahir?nip=' + $refs.nip.value).then(r => r.json()).then(d => { if(d.success) { $refs.tanggal_lahir.value = d.tanggal_lahir; nipSuccess = true; nipLoading = false; setTimeout(() => nipSuccess = false, 2000); } else { nipError = d.message || 'NIP tidak valid'; nipLoading = false; } }).catch(() => { nipError = 'Gagal memproses NIP'; nipLoading = false; })"
                                    :disabled="nipLoading"
                                    class="inline-flex items-center gap-1 px-3 py-1.5 text-sm font-medium rounded-md border border-gray-300 bg-gray-50 text-gray-700 hover:bg-gray-100 hover:border-gray-400 transition disabled:opacity-50 disabled:cursor-not-allowed">
                                <svg x-show="!nipLoading" class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                </svg>
                                <svg x-show="nipLoading" class="animate-spin w-4 h-4 text-gray-400" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                                </svg>
                                <span x-text="nipLoading ? 'Memproses...' : 'Isi Tanggal Lahir'"></span>
                            </button>
                            <span x-show="nipSuccess" x-cloak class="text-xs text-green-600">✓ Terisi</span>
                            <span x-show="nipError" x-cloak class="text-xs text-red-600" x-text="nipError"></span>
                        </div>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Nama <span class="text-red-500">*</span></label>
                        <input type="text" name="nama" value="{{ old('nama') }}" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 @error('nama') border-red-500 @enderror">
                        @error('nama')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Jenis Kepegawaian <span class="text-red-500">*</span></label>
                        <select name="jenis_kepegawaian" x-on:change="onJenisKepegawaianChange($el.value)" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                            <option value="">-- Pilih Jenis Kepegawaian --</option>
                            @foreach($jenisKepegawaianList as $val => $label)<option value="{{ $val }}" {{ old('jenis_kepegawaian') == $val ? 'selected' : '' }}>{{ $label }}</option>@endforeach
                        </select>
                        @error('jenis_kepegawaian')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Tanggal Lahir <span class="text-red-500">*</span></label>
                        <input type="date" name="tanggal_lahir" x-ref="tanggal_lahir" value="{{ old('tanggal_lahir') }}" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 @error('tanggal_lahir') border-red-500 @enderror">
                        @error('tanggal_lahir')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Golongan/Pangkat <span class="text-red-500">*</span></label>
                        <select name="golongan_pangkat" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                            <option value="">-- Pilih Golongan/Pangkat --</option>
                        </select>
                        @error('golongan_pangkat')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Pendidikan <span class="text-red-500">*</span></label>
                        <select name="pendidikan" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                            <option value="">-- Pilih Pendidikan --</option>
                            @foreach($pendidikanList as $val => $label)<option value="{{ $val }}" {{ old('pendidikan') == $val ? 'selected' : '' }}>{{ $label }}</option>@endforeach
                        </select>
                        @error('pendidikan')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Kualifikasi Pendidikan</label>
                        <input type="text" name="kualifikasi_pendidikan" value="{{ old('kualifikasi_pendidikan') }}" placeholder="Contoh: S1 Teknik Informatika" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 @error('kualifikasi_pendidikan') border-red-500 @enderror">
                        @error('kualifikasi_pendidikan')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">OPD</label>
                        <select name="induk_id" x-on:change="loadJabatan($el.value)" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 @error('induk_id') border-red-500 @enderror">
                            <option value="">-- Pilih OPD --</option>
                            @foreach($opdList as $id => $nama)<option value="{{ $id }}" {{ old('induk_id') == $id ? 'selected' : '' }}>{{ $nama }}</option>@endforeach
                        </select>
                        @error('induk_id')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                    </div>
                    <div x-show="opdSelected">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Jabatan</label>
                        <select name="jabatan_id" x-ref="jabatanSelect" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                            <option value="">-- Pilih Jabatan --</option>
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

@section('scripts')
<script>
function pegawaiForm() {
    var golonganPNS = @json($golonganPangkatList);
    var golonganPPPK = @json($pppkGolonganList);
    return {
        opdSelected: false,
        nipLoading: false,
        nipError: '',
        nipSuccess: false,
        initGolongan(jenis) {
            this.onJenisKepegawaianChange(jenis || 'PNS', '{{ old('golongan_pangkat', '') }}');
        },
        onJenisKepegawaianChange(jenis, preSelect) {
            var list = jenis === 'PPPK' ? golonganPPPK : golonganPNS;
            var select = document.querySelector('[name="golongan_pangkat"]');
            select.innerHTML = '<option value="">-- Pilih Golongan/Pangkat --</option>';
            Object.entries(list).forEach(function(_a) {
                var val = _a[0], label = _a[1];
                var opt = document.createElement('option');
                opt.value = val;
                opt.textContent = label;
                if (preSelect && val === preSelect) opt.selected = true;
                select.appendChild(opt);
            });
        },
        loadJabatan(opdId) {
            this.opdSelected = !!opdId;
            var select = this.$refs.jabatanSelect;
            select.innerHTML = '<option value="">-- Pilih Jabatan --</option>';
            if (!opdId) {
                return;
            }
            fetch('/admin/jabatan/by-opd?unor_id=' + opdId)
                .then(function(r) { return r.json(); })
                .then(function(d) {
                    select.innerHTML = '<option value="">-- Pilih Jabatan --</option>';
                    if (d.success && d.data) {
                        d.data.forEach(function(j) {
                            var opt = document.createElement('option');
                            opt.value = j.id;
                            opt.setAttribute('data-jenjang', j.jenjang || '');
                            var label = j.nama;
                            if (j.jenjang) {
                                label += ' — ' + j.jenjang;
                            }
                            if (j.unor_nama) {
                                label += ' (' + j.unor_nama + ')';
                            }
                            if (j.jenis_jabatan === 'Struktural' && j.pegawai_count >= 1) {
                                label += ' (Terisi)';
                                opt.style.color = '#ef4444';
                            }
                            opt.textContent = label;
                            select.appendChild(opt);
                        });
                    }
                })
                .catch(function() {
                    select.innerHTML = '<option value="">-- Gagal memuat --</option>';
                });
            select.onchange = null;
        }
    }
}
</script>
@append
