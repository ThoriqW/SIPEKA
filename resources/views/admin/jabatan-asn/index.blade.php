@extends('layouts.admin')

@section('content')
<div class="py-6">
    <div class="max-w-full mx-auto px-4 sm:px-6 lg:px-8">
        <div class="sm:flex sm:items-center sm:justify-between mb-6">
            <div>
                <h1 class="text-2xl font-semibold text-gray-900">Jabatan ASN</h1>
                <p class="text-sm text-gray-500 mt-1">Katalog jabatan kepegawaian — melekat pada pegawai, bukan posisi organisasi</p>
            </div>
            <a href="{{ route('admin.jabatan-asn.create') }}" class="mt-3 sm:mt-0 inline-flex items-center px-4 py-2 bg-blue-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-blue-700 focus:outline-none transition">
                + Tambah Jabatan ASN
            </a>
        </div>

        @if(session('success'))
        <div class="mb-4 px-4 py-3 bg-green-50 border border-green-200 text-green-700 rounded-md text-sm">{{ session('success') }}</div>
        @endif
        @if(session('error'))
        <div class="mb-4 px-4 py-3 bg-red-50 border border-red-200 text-red-700 rounded-md text-sm">{{ session('error') }}</div>
        @endif

        <form method="GET" class="mb-4 flex gap-3">
            <input type="text" name="search" value="{{ request('search') }}" placeholder="Cari nama..."
                   class="rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm w-64">
            <select name="jenis_jabatan" class="rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm">
                <option value="">-- Semua Jenis --</option>
                @foreach($jenisJabatanList as $key => $label)
                <option value="{{ $key }}" {{ request('jenis_jabatan') == $key ? 'selected' : '' }}>{{ $label }}</option>
                @endforeach
            </select>
            <button type="submit" class="px-3 py-1.5 bg-gray-100 border border-gray-300 rounded-md text-sm hover:bg-gray-200">Filter</button>
        </form>

        <div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider w-10">No</th>
                        <th class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Nama Jabatan ASN</th>
                        <th class="px-3 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider w-28">Jenis</th>
                        <th class="px-3 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider w-36">Jenjang</th>
                        <th class="px-3 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider w-24">Kode</th>
                        <th class="px-3 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider w-24">Pegawai</th>
                        <th class="px-3 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider w-28">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    @forelse($jabatanAsnList as $i => $item)
                    <tr class="hover:bg-gray-50">
                        <td class="px-3 py-2 text-sm text-gray-400 text-center">{{ $jabatanAsnList->firstItem() + $i }}</td>
                        <td class="px-3 py-2 text-sm text-gray-900">{{ $item->nama_jabatan_asn }}</td>
                        <td class="px-3 py-2 text-center">
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium
                                {{ $item->jenis_jabatan == 'Struktural' ? 'bg-purple-100 text-purple-800' : '' }}
                                {{ $item->jenis_jabatan == 'Fungsional' ? 'bg-blue-100 text-blue-800' : '' }}
                                {{ $item->jenis_jabatan == 'Pelaksana' ? 'bg-gray-100 text-gray-800' : '' }}">
                                {{ $item->jenis_jabatan }}
                            </span>
                        </td>
                        <td class="px-3 py-2 text-sm text-center text-gray-600">{{ $item->jenjang ?? '-' }}</td>
                        <td class="px-3 py-2 text-sm text-center text-gray-400 font-mono text-xs">{{ $item->kode_jabatan_asn }}</td>
                        <td class="px-3 py-2 text-sm text-center text-gray-600">{{ $item->pegawai_count ?? 0 }}</td>
                        <td class="px-3 py-2 text-center">
                            <a href="{{ route('admin.jabatan-asn.edit', $item) }}" class="text-blue-600 hover:text-blue-900 text-xs mr-2">Edit</a>
                            <form action="{{ route('admin.jabatan-asn.destroy', $item) }}" method="POST" class="inline" onsubmit="return confirm('Hapus jabatan ASN ini?')">
                                @csrf @method('DELETE')
                                <button type="submit" class="text-red-600 hover:text-red-900 text-xs">Hapus</button>
                            </form>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="px-6 py-10 text-center text-gray-500">Belum ada data jabatan ASN.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-4">
            {{ $jabatanAsnList->links() }}
        </div>
    </div>
</div>
@endsection
