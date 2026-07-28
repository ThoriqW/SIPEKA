@extends('layouts.admin')

@section('content')
<div class="py-6">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="mb-6">
            <h1 class="text-2xl font-semibold text-gray-900">{{ $opd->nama_unor }}</h1>
            <p class="text-sm text-gray-500 mt-1"><a href="{{ route('admin.opd.index') }}" class="hover:text-gray-700">UNOR</a> / {{ $opd->nama_unor }}</p>
        </div>

        {{-- Detail UNOR --}}
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6 mb-6">
            <h2 class="text-lg font-semibold text-gray-800 mb-4">Detail UNOR</h2>
            <table class="w-full text-sm">
                <tr class="border-b"><td class="py-3 pr-4 text-sm font-medium text-gray-500 w-48">Nama UNOR</td><td class="py-3 text-sm">{{ $opd->nama_unor }}</td></tr>
                <tr class="border-b"><td class="py-3 pr-4 text-sm font-medium text-gray-500">Kode UNOR</td><td class="py-3 text-sm">{{ $opd->kode_unor }}</td></tr>
                <tr class="border-b"><td class="py-3 pr-4 text-sm font-medium text-gray-500">Singkatan</td><td class="py-3 text-sm">{{ $opd->singkatan ?? '-' }}</td></tr>
                <tr class="border-b"><td class="py-3 pr-4 text-sm font-medium text-gray-500">Induk UNOR</td><td class="py-3 text-sm">{{ $opd->parent ? $opd->parent->nama_unor : '-' }}</td></tr>
                <tr class="border-b"><td class="py-3 pr-4 text-sm font-medium text-gray-500">Sub-UNOR</td><td class="py-3 text-sm">{{ $opd->children->count() }} UNOR</td></tr>
                <tr><td class="py-3 pr-4 text-sm font-medium text-gray-500">Total Jabatan (SOTK)</td><td class="py-3 text-sm">{{ $opd->sotkEntries->count() }} jabatan</td></tr>
            </table>
        </div>

        {{-- Daftar Jabatan di SOTK --}}
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6 mb-6">
            <div class="flex items-center justify-between mb-4">
                <h2 class="text-lg font-semibold text-gray-800">Jabatan dalam UNOR (SOTK)</h2>
            </div>

            @if($opd->sotkEntries->isNotEmpty())
            <table class="min-w-full text-sm">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">Jabatan</th>
                        <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">Jenis</th>
                        <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">Jenjang</th>
                        <th class="px-3 py-2 text-center text-xs font-medium text-gray-500 uppercase w-32">Kebutuhan</th>
                        <th class="px-3 py-2 text-right text-xs font-medium text-gray-500 uppercase">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    @foreach($opd->sotkEntries as $sotk)
                    <tr>
                        <td class="px-3 py-2">{{ $sotk->jabatan->nama_jabatan ?? '?' }}</td>
                        <td class="px-3 py-2 text-gray-500">{{ $sotk->jabatan->jenis_jabatan ?? '-' }}</td>
                        <td class="px-3 py-2 text-gray-500">{{ $sotk->jabatan->jenjang ?? '-' }}</td>
                        <td class="px-3 py-2 text-center">
                            <form method="POST" action="{{ route('admin.opd.update-kebutuhan', $opd) }}" class="inline-flex items-center gap-1">
                                @csrf
                                @method('PUT')
                                <input type="hidden" name="jabatan_id" value="{{ $sotk->jabatan_id }}">
                                <input type="number" name="jumlah" value="{{ $kebutuhanList[$sotk->jabatan_id] ?? 0 }}"
                                       min="0" class="w-16 text-center rounded border-gray-300 text-sm py-1">
                                <button type="submit" class="text-blue-600 hover:text-blue-800 text-xs">Simpan</button>
                            </form>
                        </td>
                        <td class="px-3 py-2 text-right">
                            <form method="POST" action="{{ route('admin.opd.remove-jabatan', ['opd' => $opd->id, 'sotk' => $sotk->id]) }}" onsubmit="return confirm('Hapus jabatan ini dari UNOR?')" class="inline">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="text-red-600 hover:text-red-800 text-xs">Hapus</button>
                            </form>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
            @else
            <p class="text-sm text-gray-400">Belum ada jabatan terdaftar di UNOR ini.</p>
            @endif
        </div>

        {{-- Form Tambah Jabatan ke SOTK --}}
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
            <h2 class="text-lg font-semibold text-gray-800 mb-4">Tambah Jabatan ke UNOR</h2>
            <form method="POST" action="{{ route('admin.opd.assign-jabatan', $opd) }}" class="flex items-end gap-3">
                @csrf
                <div class="flex-1">
                    <label for="jabatan_id" class="block text-sm font-medium text-gray-700">Pilih Jabatan</label>
                    <select name="jabatan_id" id="jabatan_id" required
                            class="mt-1 w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        <option value="">-- Pilih Jabatan --</option>
                        @foreach($availableJabatan as $jab)
                        <option value="{{ $jab->id }}">{{ $jab->nama_jabatan }} ({{ $jab->jenis_jabatan }}{{ $jab->jenjang ? ' - ' . $jab->jenjang : '' }})</option>
                        @endforeach
                    </select>
                </div>
                <button type="submit" class="inline-flex items-center px-4 py-2 bg-green-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-green-700 focus:outline-none transition">
                    Tambah
                </button>
            </form>
        </div>
    </div>
</div>
@endsection
