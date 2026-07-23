@extends('layouts.admin')

@section('content')
<div class="py-6">
    <div class="max-w-2xl mx-auto px-4 sm:px-6 lg:px-8">
        <h1 class="text-2xl font-semibold text-gray-900 mb-6">Tambah Jabatan ASN</h1>

        <form action="{{ route('admin.jabatan-asn.store') }}" method="POST" class="bg-white rounded-lg shadow-sm border border-gray-200 p-6 space-y-4">
            @csrf

            <div>
                <label class="block text-sm font-medium text-gray-700">Nama Jabatan ASN</label>
                <input type="text" name="nama_jabatan_asn" value="{{ old('nama_jabatan_asn') }}" required
                       class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm"
                       placeholder="Contoh: Guru Ahli Pertama, Dokter Ahli Madya">
                <p class="text-xs text-gray-400 mt-1">Nama lengkap jabatan kepegawaian termasuk level keahlian.</p>
                @error('nama_jabatan_asn') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700">Jenis Jabatan</label>
                <select name="jenis_jabatan" required id="jenis_jabatan"
                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm">
                    <option value="">-- Pilih --</option>
                    @foreach($jenisJabatanList as $val => $label)
                    <option value="{{ $val }}" {{ old('jenis_jabatan') == $val ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
                @error('jenis_jabatan') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700">Jenjang</label>
                <select name="jenjang" id="jenjang"
                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm">
                    <option value="">-- Pilih Jenjang --</option>
                </select>
                <p class="text-xs text-gray-400 mt-1">Jenjang akan tersedia setelah memilih Jenis Jabatan.</p>
                @error('jenjang') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700">Grup Induk (Parent)</label>
                <select name="parent_id"
                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm">
                    <option value="">-- Tanpa Grup --</option>
                    @foreach($parentList as $id => $nama)
                    <option value="{{ $id }}" {{ old('parent_id') == $id ? 'selected' : '' }}>{{ $nama }}</option>
                    @endforeach
                </select>
                <p class="text-xs text-gray-400 mt-1">Contoh: "Dokter Ahli Muda" bisa digrup di bawah "Dokter".</p>
                @error('parent_id') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
            </div>

            <div class="flex items-center gap-3 pt-4">
                <button type="submit" class="px-4 py-2 bg-blue-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-blue-700">Simpan</button>
                <a href="{{ route('admin.jabatan-asn.index') }}" class="px-4 py-2 bg-gray-200 border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest hover:bg-gray-300">Batal</a>
            </div>
        </form>
    </div>
</div>

<script>
// Jenjang options per jenis jabatan
const jenjangOptions = @json($jenjangOptions);

document.getElementById('jenis_jabatan').addEventListener('change', function() {
    const jenjangSelect = document.getElementById('jenjang');
    const options = jenjangOptions[this.value] || {};
    jenjangSelect.innerHTML = '<option value="">-- Pilih Jenjang --</option>';
    Object.entries(options).forEach(([key, val]) => {
        const opt = document.createElement('option');
        opt.value = key;
        opt.textContent = val;
        jenjangSelect.appendChild(opt);
    });
});

document.addEventListener('DOMContentLoaded', () => {
    if (document.getElementById('jenis_jabatan').value) {
        document.getElementById('jenis_jabatan').dispatchEvent(new Event('change'));
    }
});
</script>
@endsection
