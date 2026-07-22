@extends('layouts.admin')

@section('content')
<div class="py-6">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex items-center justify-between mb-6">
            <div>
                <h1 class="text-2xl font-semibold text-gray-900">Master Jabatan</h1>
                <p class="text-sm text-gray-500 mt-1">Kelola katalog nama jabatan standar ASN</p>
            </div>
            <a href="{{ route('admin.master-jabatan.create') }}" class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 text-sm font-medium">+ Tambah Master</a>
        </div>

        <div class="bg-white rounded-lg shadow-sm border border-gray-200" x-data="{ open: {} }">
            <div class="p-4 border-b">
                <form method="GET" class="flex flex-wrap gap-4">
                    <input type="text" name="search" placeholder="Cari nama jabatan..." value="{{ request('search') }}" class="flex-1 min-w-[200px] rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    <select name="jenis_jabatan" class="w-48 rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        <option value="">Semua Jenis</option>
                        @foreach($jenisJabatanList as $val => $label)
                            <option value="{{ $val }}" {{ request('jenis_jabatan') == $val ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                    <button type="submit" class="px-4 py-2 bg-gray-600 text-white rounded-md hover:bg-gray-700 text-sm">Cari</button>
                    @if(request('jenis_jabatan') || request('search'))
                        <a href="{{ route('admin.master-jabatan.index') }}" class="px-4 py-2 bg-gray-200 text-gray-700 rounded-md hover:bg-gray-300 text-sm">Reset</a>
                    @endif
                </form>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase w-12">No</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Nama Jabatan</th>
                            <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">Jenis</th>
                            <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase w-24">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        @php $no = 1; @endphp
                        @forelse($masterList as $m)
                            @php $hasChildren = isset($children[$m->id]) && count($children[$m->id]) > 0; @endphp
                            <tr class="hover:bg-gray-50 cursor-pointer" @if($hasChildren) x-on:click="open[{{ $m->id }}] = !open[{{ $m->id }}]" @endif>
                                <td class="px-4 py-3 text-sm text-gray-500">{{ $no++ }}</td>
                                <td class="px-4 py-3 text-sm font-medium text-gray-900">
                                    @if($hasChildren)
                                        <span x-text="open[{{ $m->id }}] ? '▼' : '▶'" class="mr-2 text-gray-400 text-xs"></span>
                                    @endif
                                    {{ $m->nama_jabatan }}
                                    @if($hasChildren)
                                        <span class="ml-1 text-xs text-gray-400">({{ count($children[$m->id]) }})</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-sm text-center">
                                    <span class="px-2 py-1 text-xs rounded-full {{ $m->jenis_jabatan === 'Struktural' ? 'bg-purple-100 text-purple-800' : ($m->jenis_jabatan === 'Fungsional' ? 'bg-blue-100 text-blue-800' : 'bg-gray-100 text-gray-800') }}">{{ $m->jenis_jabatan }}</span>
                                </td>
                                <td class="px-4 py-3 text-sm text-center">
                                    <a href="{{ route('admin.master-jabatan.edit', $m) }}" class="text-yellow-600 hover:text-yellow-900 mr-2" x-on:click.stop>Edit</a>
                                    <form action="{{ route('admin.master-jabatan.destroy', $m) }}" method="POST" class="inline" onsubmit="return confirm('Hapus master jabatan ini?')" x-on:click.stop>
                                        @csrf @method('DELETE')
                                        <button class="text-red-600 hover:text-red-900">Hapus</button>
                                    </form>
                                </td>
                            </tr>
                            {{-- Sub-jabatan (expandable) --}}
                            @if($hasChildren)
                            <template x-if="open[{{ $m->id }}]">
                                <tr><td colspan="4" class="p-0 border-0"></td></tr>
                            </template>
                            @foreach($children[$m->id] as $child)
                            <tr class="hover:bg-gray-50 bg-gray-50/50" x-show="open[{{ $m->id }}]" x-cloak>
                                <td class="px-4 py-2"></td>
                                <td class="px-4 py-2 text-sm text-gray-700">
                                    <span class="pl-8 text-gray-400">—</span> {{ $child->nama_jabatan }}
                                </td>
                                <td class="px-4 py-2 text-sm text-center">
                                    <span class="px-2 py-1 text-xs rounded-full {{ $child->jenis_jabatan === 'Struktural' ? 'bg-purple-100 text-purple-800' : ($child->jenis_jabatan === 'Fungsional' ? 'bg-blue-100 text-blue-800' : 'bg-gray-100 text-gray-800') }}">{{ $child->jenis_jabatan }}</span>
                                </td>
                                <td class="px-4 py-2 text-sm text-center">
                                    <a href="{{ route('admin.master-jabatan.edit', $child) }}" class="text-yellow-600 hover:text-yellow-900 mr-2">Edit</a>
                                    <form action="{{ route('admin.master-jabatan.destroy', $child) }}" method="POST" class="inline" onsubmit="return confirm('Hapus master jabatan ini?')">
                                        @csrf @method('DELETE')
                                        <button class="text-red-600 hover:text-red-900">Hapus</button>
                                    </form>
                                </td>
                            </tr>
                            @endforeach
                            @endif
                        @empty
                        <tr><td colspan="4" class="px-6 py-10 text-center text-gray-500">Tidak ada data master jabatan.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
