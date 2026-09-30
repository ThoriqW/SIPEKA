@extends('layouts.admin')

@section('content')
<div class="py-6">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="mb-6">
            <h1 class="text-2xl font-semibold text-gray-900">Tambah Pegawai</h1>
            <p class="text-sm text-gray-500 mt-1"><a href="{{ route('admin.pegawai.index') }}" class="hover:text-gray-700">Pegawai</a> / Tambah</p>
        </div>
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6" x-data="pegawaiForm()"
             x-init="initGolongan('{{ old('jenis_kepegawaian', '') }}'); @if(old('induk_id')) loadJabatan('{{ old('induk_id') }}', '{{ old('jabatan_id') }}') @endif">
            <form action="{{ route('admin.pegawai.store') }}" method="POST">
                @csrf
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">NIP <span class="text-red-500">*</span></label>
                        <input type="text" name="nip" x-model="nip" maxlength="18" value="{{ old('nip') }}" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 @error('nip') border-red-500 @enderror">
                        @error('nip')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
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
                        <div class="flex items-center gap-2">
                            <input type="date" name="tanggal_lahir" x-ref="tanggal_lahir" value="{{ old('tanggal_lahir') }}" class="flex-1 min-w-0 rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 @error('tanggal_lahir') border-red-500 @enderror">
                            {{-- Ikonnya sengaja bukan kalender: di sebelah input tanggal, ikon
                                 kalender terbaca sebagai "buka pemilih tanggal", padahal
                                 aksinya mengisi dari NIP. --}}
                            <button type="button"
                                    x-on:click="isiTanggalLahirDariNip()"
                                    :disabled="nipLoading || !nipSiapDiisi"
                                    title="Isi dari NIP"
                                    aria-label="Isi tanggal lahir dari NIP"
                                    class="shrink-0 inline-flex items-center justify-center p-2 rounded-md border border-gray-300 bg-gray-50 text-gray-500 hover:bg-gray-100 hover:border-gray-400 transition disabled:opacity-40 disabled:cursor-not-allowed">
                                <svg x-show="!nipLoading" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                                </svg>
                                <svg x-show="nipLoading" x-cloak class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                                </svg>
                            </button>
                            <span x-show="nipSuccess" x-cloak class="shrink-0 text-xs text-green-600">✓ Terisi</span>
                        </div>
                        @error('tanggal_lahir')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                        <p x-show="nipError" x-cloak class="mt-1 text-sm text-red-600" x-text="nipError"></p>
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
                        <label class="block text-sm font-medium text-gray-700 mb-1">Perangkat Daerah</label>
                        <select name="induk_id" x-on:change="loadJabatan($el.value)" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 @error('induk_id') border-red-500 @enderror">
                            <option value="">-- Pilih Perangkat Daerah --</option>
                            @foreach($opdList as $id => $nama)<option value="{{ $id }}" {{ old('induk_id') == $id ? 'selected' : '' }}>{{ $nama }}</option>@endforeach
                        </select>
                        @error('induk_id')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                    </div>
                    {{-- Blok ini juga tampil saat ada error jabatan, supaya pesannya tidak ikut tersembunyi --}}
                    <div x-show="opdSelected || {{ $errors->has('jabatan_id') ? 'true' : 'false' }}" x-cloak>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Jabatan <span class="text-red-500">*</span></label>
                        <select name="jabatan_id" x-ref="jabatanSelect" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 @error('jabatan_id') border-red-500 @enderror">
                            <option value="">-- Pilih Jabatan --</option>
                        </select>
                        @error('jabatan_id')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                        <p x-show="jabatanGagal" x-cloak class="mt-1 text-sm text-red-600">Daftar jabatan gagal dimuat. Pilih ulang Perangkat Daerah untuk mencoba lagi.</p>
                    </div>
                </div>
                <div class="flex gap-3 mt-6">
                    <a href="{{ route('admin.pegawai.index') }}" class="px-4 py-2 bg-gray-200 text-gray-700 rounded-md hover:bg-gray-300 text-sm">Kembali</a>
                    <button type="submit" :disabled="jabatanLoading || jabatanGagal"
                            class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 text-sm disabled:opacity-50 disabled:cursor-not-allowed">Simpan</button>
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
        jabatanLoading: false,
        jabatanGagal: false,
        nip: @json(old('nip', '')),
        nipLoading: false,
        nipError: '',
        nipSuccess: false,

        /** NIP hanya bisa diurai saat tepat 18 digit angka. */
        get nipSiapDiisi() {
            return /^\d{18}$/.test(String(this.nip).trim());
        },

        isiTanggalLahirDariNip() {
            if (!this.nipSiapDiisi) {
                return;
            }

            this.nipLoading = true;
            this.nipError = '';
            this.nipSuccess = false;

            fetch('/admin/pegawai/extract-tanggal-lahir?nip=' + encodeURIComponent(String(this.nip).trim()))
                .then(function(r) { return r.json(); })
                .then(function(d) {
                    if (d.success) {
                        this.$refs.tanggal_lahir.value = d.tanggal_lahir;
                        this.nipSuccess = true;
                        setTimeout(() => { this.nipSuccess = false; }, 2000);
                    } else {
                        this.nipError = d.message || 'NIP tidak valid';
                    }
                    this.nipLoading = false;
                }.bind(this))
                .catch(function() {
                    this.nipError = 'Gagal memproses NIP';
                    this.nipLoading = false;
                }.bind(this));
        },
        initGolongan(jenis) {
            if (jenis) {
                this.onJenisKepegawaianChange(jenis, '{{ old('golongan_pangkat', '') }}');
            }
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
        loadJabatan(opdId, preSelectId) {
            this.opdSelected = !!opdId;
            this.jabatanGagal = false;
            var select = this.$refs.jabatanSelect;
            select.innerHTML = '<option value="">-- Pilih Jabatan --</option>';
            if (!opdId) {
                this.jabatanLoading = false;
                return;
            }
            this.jabatanLoading = true;
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
                            if (j.unor_jalur) {
                                label += ' (' + j.unor_jalur + ')';
                            }
                            if (j.terisi) {
                                // Kursi struktural hanya untuk satu pegawai —
                                // jangan biarkan dipilih sejak awal.
                                label += ' (Terisi)';
                                opt.disabled = true;
                                opt.style.color = '#ef4444';
                            }
                            opt.textContent = label;
                            select.appendChild(opt);
                        });
                    }
                    // Kembalikan pilihan sebelumnya setelah validasi gagal.
                    if (preSelectId) select.value = String(preSelectId);
                    this.jabatanLoading = false;
                }.bind(this))
                .catch(function() {
                    select.innerHTML = '<option value="">-- Gagal memuat --</option>';
                    this.jabatanGagal = true;
                    this.jabatanLoading = false;
                }.bind(this));
            select.onchange = null;
        }
    }
}
</script>
@append
