@extends('layouts.admin')

@section('content')
<div class="py-6">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex items-center justify-between mb-6">
            <div>
                <h1 class="text-2xl font-semibold text-gray-900">Daftar Jabatan</h1>
                <p class="text-sm text-gray-500 mt-1">Kelola data jabatan struktural, fungsional, dan pelaksana</p>
            </div>
            <a href="{{ route('admin.jabatan.create') }}" class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 text-sm font-medium">+ Tambah Jabatan</a>
        </div>

        @if(session('success'))<div class="mb-4 p-4 bg-green-100 border border-green-400 text-green-700 rounded-md">{{ session('success') }}</div>@endif
        @if(session('error'))<div class="mb-4 p-4 bg-red-100 border border-red-400 text-red-700 rounded-md">{{ session('error') }}</div>@endif

        <div class="bg-white rounded-lg shadow-sm border border-gray-200">
            <div class="p-4 border-b">
                <form method="GET" class="flex flex-wrap gap-4">
                    <input type="text" name="search" placeholder="Cari Jabatan..." value="{{ request('search') }}" class="flex-1 min-w-[200px] rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    @if($opdList->isNotEmpty())
                    <select name="opd_id" class="w-64 rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        <option value="">Semua OPD</option>
                        @foreach($opdList as $id => $nama)<option value="{{ $id }}" {{ request('opd_id') == $id ? 'selected' : '' }}>{{ $nama }}</option>@endforeach
                    </select>
                    @endif
                    <button type="submit" class="px-4 py-2 bg-gray-600 text-white rounded-md hover:bg-gray-700 text-sm">Cari</button>
                    @if(request('search') || request('opd_id'))<a href="{{ route('admin.jabatan.index') }}" class="px-4 py-2 bg-gray-200 text-gray-700 rounded-md hover:bg-gray-300 text-sm">Reset</a>@endif
                </form>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">No</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Kode</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Nama Jabatan</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Unit Organisasi Induk</th>
                            <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">Jenis</th>
                            <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">Kelas</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Jenjang</th>
                            <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">Kebutuhan</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Unit Organisasi</th>
                            <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        @forelse($jabatanList as $key => $j)
                        <tr class="hover:bg-gray-50">
                            <td class="px-4 py-4 text-sm text-gray-500">{{ $jabatanList->firstItem() + $key }}</td>
                            <td class="px-4 py-4 text-sm text-gray-500">{{ $j->kode_jabatan }}</td>
                            <td class="px-4 py-4 text-sm font-medium text-gray-900">{{ $j->nama_jabatan }}</td>
                            <td class="px-4 py-4 text-sm text-gray-500">{{ $j->opd->nama_opd ?? '-' }}</td>
                            <td class="px-4 py-4 text-sm text-center"><span class="px-2 py-1 text-xs rounded-full {{ $j->jenis_jabatan === 'Struktural' ? 'bg-purple-100 text-purple-800' : ($j->jenis_jabatan === 'Fungsional' ? 'bg-blue-100 text-blue-800' : 'bg-gray-100 text-gray-800') }}">{{ $j->jenis_jabatan }}</span></td>
                            <td class="px-4 py-4 text-sm text-gray-500 text-center">{{ $j->kelas_jabatan }}</td>
                            <td class="px-4 py-4 text-sm text-gray-500">{{ $j->jenjang ?? '-' }}</td>
                            <td class="px-4 py-4 text-sm text-gray-500 text-center">{{ $j->kebutuhan ?? '-' }}</td>
                            <td class="px-4 py-4 text-sm text-gray-500">{{ $j->induk->nama_jabatan ?? '-' }}</td>
                            <td class="px-4 py-4 text-sm text-center">
                                <a href="{{ route('admin.jabatan.edit', $j) }}" class="text-yellow-600 hover:text-yellow-900 mr-2">Edit</a>
                                <form action="{{ route('admin.jabatan.destroy', $j) }}" method="POST" class="inline" onsubmit="return confirm('Hapus jabatan ini?')">
                                    @csrf @method('DELETE')
                                    <button class="text-red-600 hover:text-red-900">Hapus</button>
                                </form>
                            </td>
                        </tr>
                        @empty
                        <tr><td colspan="10" class="px-6 py-10 text-center text-gray-500">Tidak ada data jabatan.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if($jabatanList->hasPages())<div class="px-6 py-4 border-t">{{ $jabatanList->links() }}</div>@endif
        </div>
    </div>
</div>
@endsection
