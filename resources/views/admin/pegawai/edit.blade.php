@extends('layouts.admin')

@section('content')
<div class="py-6">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="mb-6">
            <h1 class="text-2xl font-semibold text-gray-900">Edit Pegawai</h1>
            <p class="text-sm text-gray-500 mt-1"><a href="{{ route('admin.pegawai.index') }}" class="hover:text-gray-700">Pegawai</a> / Edit</p>
        </div>
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6" x-data="pegawaiForm()" x-init="initGolongan('{{ old('jenis_kepegawaian', $pegawai->jenis_kepegawaian) }}'); @if($currentIndukId) loadJabatan({{ $currentIndukId }}) @endif">
            <form action="{{ route('admin.pegawai.update', $pegawai) }}" method="POST">
                @csrf @method('PUT')
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">NIP (18 digit) <span class="text-red-500">*</span></label>
                        <input type="text" name="nip" x-ref="nip" maxlength="18" value="{{ old('nip', $pegawai->nip) }}" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 @error('nip') border-red-500 @enderror">
                        @error('nip')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                        <button type="button" x-on:click="fetch('/admin/pegawai/extract-tanggal-lahir?nip=' + $refs.nip.value).then(r => r.json()).then(d => { if(d.success) $refs.tanggal_lahir.value = d.tanggal_lahir })" class="mt-2 text-sm text-blue-600 hover:text-blue-800">Isi Otomatis Tanggal Lahir dari NIP</button>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Nama <span class="text-red-500">*</span>
                        <input type="text" name="nama" value="{{ old('nama', $pegawai->nama) }}" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 @error('nama') border-red-500 @enderror">
                        @error('nama')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Jenis Kepegawaian <span class="text-red-500">*</span></label>
                        <select name="jenis_kepegawaian" x-on:change="onJenisKepegawaianChange($el.value)" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                            @foreach($jenisKepegawaianList as $val => $label)<option value="{{ $val }}" {{ old('jenis_kepegawaian', $pegawai->jenis_kepegawaian) == $val ? 'selected' : '' }}>{{ $label }}</option>@endforeach
                        </select>
                        @error('jenis_kepegawaian')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Tanggal Lahir <span class="text-red-500">*</span></label>
                        <input type="date" name="tanggal_lahir" x-ref="tanggal_lahir" value="{{ old('tanggal_lahir', $pegawai->tanggal_lahir ? $pegawai->tanggal_lahir->format('Y-m-d') : '') }}" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Golongan/Pangkat <span class="text-red-500">*</span></label>
                        <select name="golongan_pangkat" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 @error('golongan_pangkat') border-red-500 @enderror">
                            <option value="">-- Pilih Golongan/Pangkat --</option>
                        </select>
                        @error('golongan_pangkat')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Pendidikan <span class="text-red-500">*</span></label>
                        <select name="pendidikan" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                            @foreach($pendidikanList as $val => $label)<option value="{{ $val }}" {{ old('pendidikan', $pegawai->pendidikan) == $val ? 'selected' : '' }}>{{ $label }}</option>@endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Kualifikasi Pendidikan</label>
                        <input type="text" name="kualifikasi_pendidikan" value="{{ old('kualifikasi_pendidikan', $pegawai->kualifikasi_pendidikan) }}" placeholder="Contoh: S1 Teknik Informatika" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">OPD</label>
                        <select name="induk_id" x-on:change="loadJabatan($el.value)" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 @error('induk_id') border-red-500 @enderror">
                            <option value="">-- Pilih OPD --</option>
                            @foreach($opdList as $id => $nama)<option value="{{ $id }}" {{ old('induk_id', $currentIndukId) == $id ? 'selected' : '' }}>{{ $nama }}</option>@endforeach
                        </select>
                        @error('induk_id')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                    </div>
                    <div x-show="opdSelected">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Jabatan</label>
                        <select name="jabatan_id" x-ref="jabatanSelect" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                            <option value="">-- Pilih Jabatan --</option>
                        </select>
                    </div>
                </div>
                <div class="flex gap-3 mt-6">
                    <a href="{{ route('admin.pegawai.index') }}" class="px-4 py-2 bg-gray-200 text-gray-700 rounded-md hover:bg-gray-300 text-sm">Kembali</a>
                    <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 text-sm">Perbarui</button>
                </div>
            </form>
        </div>

        {{-- Tugas Tambahan --}}
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6 mt-6">
            <h2 class="text-lg font-semibold text-gray-900 mb-4">Tugas Tambahan</h2>

            {{-- Daftar tugas tambahan aktif --}}
            @php
                $today = now()->toDateString();
                $aktifTugas = $pegawai->tugasTambahan->filter(function($tt) use ($today) {
                    return $tt->is_active && ($tt->tanggal_selesai === null || $tt->tanggal_selesai->format('Y-m-d') >= $today);
                });
            @endphp
            @if($aktifTugas->isNotEmpty())
            <div class="mb-6">
                <table class="min-w-full divide-y divide-gray-200 border rounded-md">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Tugas</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">UNOR</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Tgl Mulai</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Tgl Selesai</th>
                            <th class="px-4 py-2 text-center text-xs font-medium text-gray-500 uppercase w-20">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        @foreach($aktifTugas as $tt)
                        <tr class="hover:bg-gray-50">
                            <td class="px-4 py-2 text-sm text-gray-900">{{ $tt->tugasTambahan->nama_tugas }}</td>
                            <td class="px-4 py-2 text-sm text-gray-500">{{ $tt->unor->nama_unor ?? '-' }}</td>
                            <td class="px-4 py-2 text-sm text-gray-500">{{ $tt->tanggal_mulai ? $tt->tanggal_mulai->format('d/m/Y') : '-' }}</td>
                            <td class="px-4 py-2 text-sm text-gray-500">{{ $tt->tanggal_selesai ? $tt->tanggal_selesai->format('d/m/Y') : '-' }}</td>
                            <td class="px-4 py-2 text-sm text-center">
                                <form action="{{ route('admin.pegawai.tugas-tambahan.cabut', [$pegawai, $tt]) }}" method="POST" class="inline" onsubmit="return confirm('Cabut tugas tambahan ini?')">
                                    @csrf @method('PATCH')
                                    <button class="text-yellow-600 hover:text-yellow-900 mr-2">Cabut</button>
                                </form>
                                <form action="{{ route('admin.pegawai.tugas-tambahan.destroy', [$pegawai, $tt]) }}" method="POST" class="inline" onsubmit="return confirm('Hapus permanen record ini?')">
                                    @csrf @method('DELETE')
                                    <button class="text-red-600 hover:text-red-900">Hapus</button>
                                </form>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @else
            <p class="text-sm text-gray-400 mb-4">Belum ada tugas tambahan.</p>
            @endif

            {{-- Form tambah tugas tambahan --}}
            <div class="border-t pt-4">
                <h3 class="text-sm font-medium text-gray-700 mb-3">Tambah / Perbarui Tugas Tambahan</h3>
                <form action="{{ route('admin.pegawai.tugas-tambahan.store', $pegawai) }}" method="POST">
                    @csrf
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Tugas Tambahan</label>
                            <select name="tugas_tambahan_id" required class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                <option value="">-- Pilih --</option>
                                @foreach($tugasTambahanList as $t)
                                <option value="{{ $t->id }}">{{ $t->nama_tugas }}</option>
                                @endforeach
                            </select>
                        </div>
                        {{-- Unit Organisasi — searchable dropdown --}}
                        <div x-data="{ openUnor: false, searchUnor: '', selectedText: '' }" class="relative">
                            <input type="hidden" name="unor_id" x-ref="unorId">
                            <label class="block text-sm font-medium text-gray-700 mb-1">Unit Organisasi</label>
                            <div class="relative">
                                <input type="text" x-model="searchUnor" x-ref="searchUnorInput"
                                       @focus="openUnor = true" @click="openUnor = true"
                                       @input="openUnor = true"
                                       @blur="setTimeout(() => { openUnor = false; if (searchUnor && searchUnor !== selectedText) searchUnor = '' }, 150)"
                                       :placeholder="selectedText || 'Cari Unit Organisasi'"
                                       class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 pr-8">
                                <span class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 pointer-events-none"
                                      @click.stop="$refs.searchUnorInput.focus(); openUnor = true">▾</span>
                            </div>
                            <div x-show="openUnor" x-cloak
                                 @mousedown.prevent
                                 class="absolute z-50 mt-1 w-full bg-white border border-gray-300 rounded-md shadow-lg max-h-48 overflow-y-auto">
                                @foreach($unorList as $id => $nama)
                                <div @click="openUnor = false; searchUnor = ''; selectedText = '{{ $nama }}'; $refs.unorId.value = '{{ $id }}'"
                                     x-show="!searchUnor || '{{ strtolower($nama) }}'.includes(searchUnor.toLowerCase())"
                                     class="px-3 py-2 text-sm hover:bg-blue-50 cursor-pointer">{{ $nama }}</div>
                                @endforeach
                            </div>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Tanggal Mulai</label>
                            <input type="date" name="tanggal_mulai" required class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500" value="{{ date('Y-m-d') }}">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Tanggal Selesai</label>
                            <input type="date" name="tanggal_selesai" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        </div>
                    </div>
                    <button type="submit" class="mt-4 px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 text-sm">+ Tambah Tugas</button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
function pegawaiForm() {
    var currentJabatanId = {{ $pegawai->jabatan_id ?? 'null' }};
    var golonganPNS = @json($golonganPangkatList);
    var golonganPPPK = @json($pppkGolonganList);
    var currentGolongan = '{{ old('golongan_pangkat', $pegawai->golongan_pangkat) }}';
    var currentJenis = '{{ old('jenis_kepegawaian', $pegawai->jenis_kepegawaian) }}';
    return {
        opdSelected: false,
        initGolongan(jenis) {
            this.onJenisKepegawaianChange(jenis || 'PNS', currentGolongan);
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
                            if (j.id == currentJabatanId) {
                                // Allow current jabatan even if full (own position)
                            } else if (j.jenis_jabatan === 'Struktural' && j.pegawai_count >= 1) {
                                label += ' (Terisi)';
                                opt.style.color = '#ef4444';
                            }
                            opt.textContent = label;
                            if (j.id == currentJabatanId) opt.selected = true;
                            select.appendChild(opt);
                        });
                    }
                }.bind(this))
                .catch(function() {
                    select.innerHTML = '<option value="">-- Gagal memuat --</option>';
                });
            select.onchange = null;
        }
    }
}
</script>
@append
