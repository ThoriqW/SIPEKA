@extends('layouts.admin')

@section('content')
<div class="py-6">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="mb-6">
            <h1 class="text-2xl font-semibold text-gray-900">Edit Pegawai</h1>
            <p class="text-sm text-gray-500 mt-1"><a href="{{ route('admin.pegawai.index') }}" class="hover:text-gray-700">Pegawai</a> / Edit</p>
        </div>
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6" x-data="pegawaiForm()" x-init="loadJabatan({{ $pegawai->opd_id }})">
            <form action="{{ route('admin.pegawai.update', $pegawai) }}" method="POST">
                @csrf @method('PUT')
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">NIP (18 digit)</label>
                        <input type="text" name="nip" x-ref="nip" maxlength="18" value="{{ old('nip', $pegawai->nip) }}" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 @error('nip') border-red-500 @enderror">
                        @error('nip')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                        <button type="button" x-on:click="fetch('/admin/pegawai/extract-tanggal-lahir?nip=' + $refs.nip.value).then(r => r.json()).then(d => { if(d.success) $refs.tanggal_lahir.value = d.tanggal_lahir })" class="mt-2 text-sm text-blue-600 hover:text-blue-800">Isi Otomatis Tanggal Lahir dari NIP</button>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Nama</label>
                        <input type="text" name="nama" value="{{ old('nama', $pegawai->nama) }}" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 @error('nama') border-red-500 @enderror">
                        @error('nama')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Jenis Kepegawaian</label>
                        <select name="jenis_kepegawaian" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                            @foreach($jenisKepegawaianList as $val => $label)<option value="{{ $val }}" {{ old('jenis_kepegawaian', $pegawai->jenis_kepegawaian) == $val ? 'selected' : '' }}>{{ $label }}</option>@endforeach
                        </select>
                        @error('jenis_kepegawaian')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Tanggal Lahir</label>
                        <input type="date" name="tanggal_lahir" x-ref="tanggal_lahir" value="{{ old('tanggal_lahir', $pegawai->tanggal_lahir ? $pegawai->tanggal_lahir->format('Y-m-d') : '') }}" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Golongan/Pangkat</label>
                        <select name="golongan_pangkat" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                            @foreach($golonganPangkatList as $val => $label)<option value="{{ $val }}" {{ old('golongan_pangkat', $pegawai->golongan_pangkat) == $val ? 'selected' : '' }}>{{ $label }}</option>@endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Pendidikan</label>
                        <select name="pendidikan" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                            @foreach($pendidikanList as $val => $label)<option value="{{ $val }}" {{ old('pendidikan', $pegawai->pendidikan) == $val ? 'selected' : '' }}>{{ $label }}</option>@endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">OPD</label>
                        <select name="opd_id" x-on:change="loadJabatan($el.value)" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                            @foreach($opdList as $id => $nama)<option value="{{ $id }}" {{ old('opd_id', $pegawai->opd_id) == $id ? 'selected' : '' }}>{{ $nama }}</option>@endforeach
                        </select>
                    </div>
                    <div x-show="opdSelected">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Jabatan</label>
                        <select name="jabatan_id" x-ref="jabatanSelect" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                            <option value="">-- Memuat... --</option>
                        </select>
                    </div>
                </div>
                <div class="flex gap-3 mt-6">
                    <a href="{{ route('admin.pegawai.index') }}" class="px-4 py-2 bg-gray-200 text-gray-700 rounded-md hover:bg-gray-300 text-sm">Kembali</a>
                    <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 text-sm">Perbarui</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
function pegawaiForm() {
    var currentJabatanId = {{ $pegawai->jabatan_id ?? 'null' }};
    return {
        opdSelected: true,
        loadJabatan(opdId) {
            this.opdSelected = !!opdId;
            var select = this.$refs.jabatanSelect;
            select.innerHTML = '<option value="">-- Memuat... --</option>';
            if (!opdId) {
                return;
            }
            fetch('/admin/jabatan/by-opd?opd_id=' + opdId)
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
