@extends('layouts.admin')

@section('content')
<div class="py-6">
    <div class="max-w-full mx-auto px-4 sm:px-6 lg:px-8">
        <div class="sm:flex sm:items-center sm:justify-between mb-6">
            <div>
                <h1 class="text-2xl font-semibold text-gray-900">Kebutuhan Jabatan</h1>
                <p class="text-sm text-gray-500 mt-1">Formasi posisi yang dapat diisi pegawai — satu posisi = satu kebutuhan</p>
            </div>
            <a href="{{ route('admin.kebutuhan-jabatan.create') }}" class="mt-3 sm:mt-0 inline-flex items-center px-4 py-2 bg-blue-600 border border-transparent rounded-md font-semibold text-sm text-white hover:bg-blue-700 focus:outline-none transition">
                + Tambah Posisi
            </a>
        </div>

        <!-- Filter -->
        <form method="GET" class="mb-4 flex gap-3 items-end">
            <div>
                <label class="block text-xs text-gray-500 mb-1">Cari</label>
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Nama posisi..."
                       class="rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm w-56">
            </div>
            <div>
                <label class="block text-xs text-gray-500 mb-1">Unit Induk</label>
                <select name="unit_id" class="rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm">
                    <option value="">-- Semua Unit --</option>
                    @foreach($unitList as $unit)
                    <option value="{{ $unit->id }}" {{ request('unit_id') == $unit->id ? 'selected' : '' }}>{{ $unit->nama }}</option>
                    @endforeach
                </select>
            </div>
            <button type="submit" class="px-3 py-1.5 bg-gray-100 border border-gray-300 rounded-md text-sm hover:bg-gray-200">Filter</button>
            @if(request()->filled('unit_id'))
            <a href="{{ route('admin.kebutuhan-jabatan.index') }}" class="text-sm text-blue-600 hover:text-blue-800">↻ Reset</a>
            @endif
        </form>

        <!-- Total -->
        <div class="mb-4 flex gap-4">
            <div class="bg-white rounded-lg border border-gray-200 px-4 py-2 text-sm">
                <span class="text-gray-500">Total Kebutuhan:</span>
                <span class="font-bold text-blue-700 ml-1">{{ $posisiList->flatten()->count() }}</span>
                <span class="text-gray-400 ml-1">posisi</span>
            </div>
            <div class="bg-white rounded-lg border border-gray-200 px-4 py-2 text-sm">
                <span class="text-gray-500">Terisi:</span>
                <span class="font-bold text-green-700 ml-1">{{ $posisiList->flatten()->filter(fn($p) => $p->isTerisi())->count() }}</span>
                <span class="text-gray-400 ml-1">posisi</span>
            </div>
            <div class="bg-white rounded-lg border border-gray-200 px-4 py-2 text-sm">
                <span class="text-gray-500">Kosong:</span>
                <span class="font-bold text-red-700 ml-1">{{ $posisiList->flatten()->filter(fn($p) => !$p->isTerisi())->count() }}</span>
                <span class="text-gray-400 ml-1">posisi</span>
            </div>
        </div>

        <!-- Tabel POSISI dikelompokkan per UNIT -->
        @forelse($posisiList as $parentId => $posisiItems)
            @php $parentUnit = $posisiItems->first()->parent; @endphp
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden mb-4" x-data="{ open: true }">
                <!-- Header Unit -->
                <button @click="open = !open"
                        class="w-full flex items-center justify-between px-4 py-3 bg-blue-50 hover:bg-blue-100 border-b border-blue-100 transition">
                    <div class="flex items-center gap-3">
                        <span x-text="open ? '▾' : '▸'" class="text-blue-600 text-sm"></span>
                        <span class="font-semibold text-gray-900">{{ $parentUnit?->nama ?? 'Tanpa Unit' }}</span>
                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                            {{ $posisiItems->count() }} posisi
                        </span>
                    </div>
                    <span class="text-xs text-gray-400">{{ $posisiItems->filter(fn($p) => $p->isTerisi())->count() }} terisi</span>
                </button>

                <!-- Isi -->
                <div x-show="open">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase w-10">No</th>
                                <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">Nama Posisi</th>
                                <th class="px-3 py-2 text-center text-xs font-medium text-gray-500 uppercase w-16">Kelas</th>
                                <th class="px-3 py-2 text-center text-xs font-medium text-gray-500 uppercase w-16">Kode</th>
                                <th class="px-3 py-2 text-center text-xs font-medium text-gray-500 uppercase w-24">Status</th>
                                <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">Diisi Oleh</th>
                                <th class="px-3 py-2 text-center text-xs font-medium text-gray-500 uppercase w-24">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @foreach($posisiItems as $i => $posisi)
                            <tr class="hover:bg-gray-50/50">
                                <td class="px-3 py-2 text-sm text-gray-400 text-center">{{ $i + 1 }}</td>
                                <td class="px-3 py-2 text-sm text-gray-900">
                                    {{ $posisi->nama }}
                                </td>
                                <td class="px-3 py-2 text-sm text-center text-gray-600">{{ $posisi->kelas_jabatan ?? '-' }}</td>
                                <td class="px-3 py-2 text-sm text-center text-gray-400 font-mono text-xs">{{ $posisi->kode ?? '-' }}</td>
                                <td class="px-3 py-2 text-center">
                                    @if($posisi->isTerisi())
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">Terisi</span>
                                    @else
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-600">Kosong</span>
                                    @endif
                                </td>
                                <td class="px-3 py-2 text-sm text-gray-500">
                                    @if($posisi->isTerisi())
                                        @php $p = $posisi->pegawai->first(); @endphp
                                        {{ $p->nama }} <span class="text-xs text-gray-400">({{ $p->jabatanAsn?->nama_jabatan_asn ?? $p->nip }})</span>
                                    @else
                                    <span class="text-gray-300">-</span>
                                    @endif
                                </td>
                                <td class="px-3 py-2 text-center">
                                    <a href="{{ route('admin.kebutuhan-jabatan.edit', $posisi) }}" class="text-blue-600 hover:text-blue-900 text-xs mr-2">Edit</a>
                                    @if(!$posisi->isTerisi())
                                    <form action="{{ route('admin.kebutuhan-jabatan.destroy', $posisi) }}" method="POST" class="inline" onsubmit="return confirm('Hapus posisi ini?')">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="text-red-600 hover:text-red-900 text-xs">Hapus</button>
                                    </form>
                                    @endif
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @empty
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-10 text-center text-gray-500">
            Belum ada data posisi (kebutuhan jabatan).
            <br>
            <a href="{{ route('admin.kebutuhan-jabatan.create') }}" class="text-blue-600 hover:text-blue-800 mt-2 inline-block">+ Tambah Posisi pertama</a>
        </div>
        @endforelse
    </div>
</div>
@endsection
