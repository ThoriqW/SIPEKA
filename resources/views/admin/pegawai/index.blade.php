@extends('layouts.admin')

@section('content')
<div class="py-6">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex items-center justify-between mb-6">
            <div>
                <h1 class="text-2xl font-semibold text-gray-900">Daftar Pegawai</h1>
                <p class="text-sm text-gray-500 mt-1">Kelola data pegawai ASN</p>
            </div>
            <a href="{{ route('admin.pegawai.create') }}" class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 text-sm font-medium">+ Tambah Pegawai</a>
        </div>

        @if(session('success'))<div class="mb-4 p-4 bg-green-100 border border-green-400 text-green-700 rounded-md">{{ session('success') }}</div>@endif

        <div class="bg-white rounded-lg shadow-sm border border-gray-200">
            <div class="p-4 border-b">
                <form method="GET" class="flex flex-wrap gap-4">
                    <input type="text" name="search" placeholder="Cari Nama atau NIP..." value="{{ request('search') }}" class="flex-1 min-w-[200px] rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    @if($opdList->isNotEmpty())
                    <select name="opd_id" class="w-64 rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        <option value="">Semua OPD</option>
                        @foreach($opdList as $id => $nama)<option value="{{ $id }}" {{ request('opd_id') == $id ? 'selected' : '' }}>{{ $nama }}</option>@endforeach
                    </select>
                    @endif
                    <button type="submit" class="px-4 py-2 bg-gray-600 text-white rounded-md hover:bg-gray-700 text-sm">Cari</button>
                    @if(request('search') || request('opd_id'))<a href="{{ route('admin.pegawai.index') }}" class="px-4 py-2 bg-gray-200 text-gray-700 rounded-md hover:bg-gray-300 text-sm">Reset</a>@endif
                </form>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">No</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">NIP</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Nama</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">OPD</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Jabatan</th>
                            <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase">Status</th>
                            <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        @forelse($pegawaiList as $key => $p)
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-4 text-sm text-gray-500">{{ $pegawaiList->firstItem() + $key }}</td>
                            <td class="px-6 py-4 text-sm text-gray-500">{{ $p->nip }}</td>
                            <td class="px-6 py-4 text-sm font-medium text-gray-900">{{ $p->nama }}</td>
                            <td class="px-6 py-4 text-sm text-gray-500">{{ $p->opd->nama_opd ?? '-' }}</td>
                            <td class="px-6 py-4 text-sm text-gray-500">{{ $p->jabatan->nama_jabatan ?? '-' }}</td>
                            <td class="px-6 py-4 text-sm text-center"><span class="px-2 py-1 text-xs rounded-full {{ $p->jenis_kepegawaian === 'PNS' ? 'bg-green-100 text-green-800' : 'bg-blue-100 text-blue-800' }}">{{ $p->jenis_kepegawaian }}</span></td>
                            <td class="px-6 py-4 text-sm text-center">
                                <a href="{{ route('admin.pegawai.edit', $p) }}" class="text-yellow-600 hover:text-yellow-900 mr-2">Edit</a>
                                <form action="{{ route('admin.pegawai.destroy', $p) }}" method="POST" class="inline" onsubmit="return confirm('Hapus pegawai ini?')">
                                    @csrf @method('DELETE')
                                    <button class="text-red-600 hover:text-red-900">Hapus</button>
                                </form>
                            </td>
                        </tr>
                        @empty
                        <tr><td colspan="7" class="px-6 py-10 text-center text-gray-500">Tidak ada data pegawai.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if($pegawaiList->hasPages())<div class="px-6 py-4 border-t">{{ $pegawaiList->links() }}</div>@endif
        </div>
    </div>
</div>
@endsection
