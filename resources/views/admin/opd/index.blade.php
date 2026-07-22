@extends('layouts.admin')

@section('content')
<div class="py-6">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex items-center justify-between mb-6">
            <div>
                <h1 class="text-2xl font-semibold text-gray-900">Daftar OPD</h1>
                <p class="text-sm text-gray-500 mt-1">Kelola data Organisasi Perangkat Daerah</p>
            </div>
            <a href="{{ route('admin.opd.create') }}" class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 text-sm font-medium">+ Tambah OPD</a>
        </div>

        <div class="bg-white rounded-lg shadow-sm border border-gray-200">
            <div class="p-4 border-b">
                <form method="GET" class="flex gap-4">
                    <input type="text" name="search" placeholder="Cari OPD..." value="{{ request('search') }}" class="flex-1 rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    <button type="submit" class="px-4 py-2 bg-gray-600 text-white rounded-md hover:bg-gray-700 text-sm">Cari</button>
                    @if(request('search'))<a href="{{ route('admin.opd.index') }}" class="px-4 py-2 bg-gray-200 text-gray-700 rounded-md hover:bg-gray-300 text-sm">Reset</a>@endif
                </form>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">No</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Nama OPD</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Kode</th>
                            <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase">Jabatan</th>
                            <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase">Pegawai</th>
                            <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        @forelse($opdList as $key => $opd)
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-4 text-sm text-gray-500">{{ $opdList->firstItem() + $key }}</td>
                            <td class="px-6 py-4 text-sm font-medium text-gray-900">{{ $opd->nama_opd }}</td>
                            <td class="px-6 py-4 text-sm text-gray-500">{{ $opd->kode_opd }}</td>
                            <td class="px-6 py-4 text-sm text-gray-500 text-center">{{ $opd->jabatan_count }}</td>
                            <td class="px-6 py-4 text-sm text-gray-500 text-center">{{ $opd->pegawai_count }}</td>
                            <td class="px-6 py-4 text-sm text-center">
                                <a href="{{ route('admin.opd.show', $opd) }}" class="text-blue-600 hover:text-blue-900 mr-2">Lihat</a>
                                <a href="{{ route('admin.opd.edit', $opd) }}" class="text-yellow-600 hover:text-yellow-900 mr-2">Edit</a>
                                <form action="{{ route('admin.opd.destroy', $opd) }}" method="POST" class="inline" onsubmit="return confirm('Hapus OPD ini?')">
                                    @csrf @method('DELETE')
                                    <button class="text-red-600 hover:text-red-900">Hapus</button>
                                </form>
                            </td>
                        </tr>
                        @empty
                        <tr><td colspan="6" class="px-6 py-10 text-center text-gray-500">Tidak ada data OPD.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if($opdList->hasPages())<div class="px-6 py-4 border-t">{{ $opdList->links() }}</div>@endif
        </div>
    </div>
</div>
@endsection
