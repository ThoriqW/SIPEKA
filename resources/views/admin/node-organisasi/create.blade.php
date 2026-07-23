@extends('layouts.admin')

@section('content')
<div class="py-6">
    <div class="max-w-2xl mx-auto px-4 sm:px-6 lg:px-8">
        <h1 class="text-2xl font-semibold text-gray-900 mb-6">Tambah Node Organisasi</h1>

        <form action="{{ route('admin.node-organisasi.store') }}" method="POST" class="bg-white rounded-lg shadow-sm border border-gray-200 p-6 space-y-4">
            @csrf

            <!-- Nama -->
            <div>
                <label class="block text-sm font-medium text-gray-700">Nama</label>
                <input type="text" name="nama" value="{{ old('nama') }}" required
                       class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm"
                       placeholder="Contoh: Kepala BKPSDMD, Puskesmas Talise, Dokter">
                @error('nama') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
            </div>

            <!-- Jenis -->
            <div>
                <label class="block text-sm font-medium text-gray-700">Jenis Node</label>
                <select name="jenis" required id="jenis"
                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm">
                    <option value="">-- Pilih Jenis --</option>
                    @foreach($jenisOptions as $val => $label)
                    <option value="{{ $val }}" {{ old('jenis') == $val ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
                <p class="text-xs text-gray-400 mt-1">UNIT = wadah organisasi (tidak bisa diisi). POSISI = jabatan yang bisa diisi pegawai.</p>
                @error('jenis') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
            </div>

            <!-- Parent -->
            <div>
                <label class="block text-sm font-medium text-gray-700">Induk (Parent)</label>
                <select name="parent_id"
                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm">
                    <option value="">-- Tanpa Induk (Root Level) --</option>
                    @foreach($parentOptions as $opt)
                    <option value="{{ $opt['id'] }}" {{ old('parent_id') == $opt['id'] ? 'selected' : '' }}>{{ $opt['nama'] }}</option>
                    @endforeach
                </select>
                <p class="text-xs text-gray-400 mt-1">Biarkan kosong jika node ini langsung di bawah "Pemerintah Kota Palu".</p>
                @error('parent_id') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
            </div>

            <!-- Kelas Jabatan (hanya POSISI) -->
            <div id="kelas-wrapper">
                <label class="block text-sm font-medium text-gray-700">Kelas Jabatan</label>
                <input type="number" name="kelas_jabatan" value="{{ old('kelas_jabatan') }}" min="1"
                       class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm w-32"
                       placeholder="Misal: 7">
                <p class="text-xs text-gray-400 mt-1">Hanya untuk POSISI. Kosongkan untuk UNIT.</p>
                @error('kelas_jabatan') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
            </div>

            <!-- Sort Order -->
            <div>
                <label class="block text-sm font-medium text-gray-700">Urutan (Sort Order)</label>
                <input type="number" name="sort_order" value="{{ old('sort_order', 0) }}" min="0"
                       class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm w-24">
            </div>

            <div class="flex items-center gap-3 pt-4">
                <button type="submit" class="px-4 py-2 bg-blue-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-blue-700">
                    Simpan
                </button>
                <a href="{{ route('admin.node-organisasi.index') }}" class="px-4 py-2 bg-gray-200 border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest hover:bg-gray-300">
                    Batal
                </a>
            </div>
        </form>
    </div>
</div>

<script>
document.getElementById('jenis').addEventListener('change', function() {
    const kelasWraper = document.getElementById('kelas-wrapper');
    if (this.value === 'UNIT') {
        kelasWraper.style.opacity = '0.5';
        kelasWraper.querySelector('input').value = '';
        kelasWraper.querySelector('input').disabled = true;
    } else {
        kelasWraper.style.opacity = '1';
        kelasWraper.querySelector('input').disabled = false;
    }
});
// Init on load
window.addEventListener('DOMContentLoaded', () => {
    document.getElementById('jenis').dispatchEvent(new Event('change'));
});
</script>
@endsection
