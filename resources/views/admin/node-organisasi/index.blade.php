@extends('layouts.admin')

@section('content')
<div class="py-6">
    <div class="max-w-full mx-auto px-4 sm:px-6 lg:px-8">
        <div class="sm:flex sm:items-center sm:justify-between mb-6">
            <div>
                <h1 class="text-2xl font-semibold text-gray-900">Struktur Organisasi</h1>
                <p class="text-sm text-gray-500 mt-1">Pohon organisasi — Unit Organisasi & Posisi</p>
            </div>
            <a href="{{ route('admin.node-organisasi.create') }}" class="mt-3 sm:mt-0 inline-flex items-center px-4 py-2 bg-blue-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-blue-700 focus:outline-none transition">
                + Tambah Node
            </a>
        </div>

        @if(session('success'))
        <div class="mb-4 px-4 py-3 bg-green-50 border border-green-200 text-green-700 rounded-md text-sm">
            {{ session('success') }}
        </div>
        @endif
        @if(session('error'))
        <div class="mb-4 px-4 py-3 bg-red-50 border border-red-200 text-red-700 rounded-md text-sm">
            {{ session('error') }}
        </div>
        @endif

        <!-- Search & Filter -->
        <form method="GET" class="mb-4 flex gap-3">
            <input type="text" name="search" value="{{ request('search') }}" placeholder="Cari nama node..."
                   class="rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm w-64">
            <select name="jenis" class="rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm">
                <option value="">-- Semua Jenis --</option>
                <option value="UNIT" {{ request('jenis') == 'UNIT' ? 'selected' : '' }}>Unit Organisasi</option>
                <option value="POSISI" {{ request('jenis') == 'POSISI' ? 'selected' : '' }}>Posisi Organisasi</option>
            </select>
            <button type="submit" class="px-3 py-1.5 bg-gray-100 border border-gray-300 rounded-md text-sm hover:bg-gray-200">Filter</button>
        </form>

        <div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider w-10">No</th>
                        <th class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Nama</th>
                        <th class="px-3 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider w-20">Jenis</th>
                        <th class="px-3 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider w-16">Kelas</th>
                        <th class="px-3 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider w-16">Kode</th>
                        <th class="px-3 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider w-24">Pegawai</th>
                        <th class="px-3 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider w-28">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200" x-data="treeData()">
                    @php
                        function renderNode($node, $childrenMap, $level, &$no) {
                            $indent = ($level - 1) * 28;
                            $hasChildren = isset($childrenMap[$node->id]);
                            $isUnit = $node->jenis === 'UNIT';
                            $isPosisi = $node->jenis === 'POSISI';
                            $badgeClass = $isUnit ? 'bg-purple-100 text-purple-800' : 'bg-blue-100 text-blue-800';
                            $pegawaiCount = $node->pegawai->count();
                    @endphp
                    <tr data-id="{{ $node->id }}"
                        data-parent-id="{{ $node->parent_id ?? '' }}"
                        data-level="{{ $level }}"
                        x-show="isVisible({{ $node->id }}, '{{ $node->parent_id ?? '0' }}')"
                        @if($hasChildren)
                        @click="toggleNode({{ $node->id }})"
                        class="cursor-pointer {{ $level <= 2 ? 'bg-gray-50/50' : '' }} hover:bg-blue-50/30"
                        @else
                        class="{{ $level <= 2 ? 'bg-gray-50/50' : '' }} hover:bg-blue-50/30"
                        @endif
                    >
                        <td class="px-2 py-2 text-sm text-gray-400 text-center">{{ $no++ }}</td>
                        <td class="py-2 pr-2 text-sm {{ $level <= 1 ? 'font-semibold text-gray-900' : 'text-gray-700' }}"
                            style="padding-left: {{ $indent + 8 }}px;">
                            @if($hasChildren)
                            <span class="text-gray-400 mr-0.5" x-text="isExpanded({{ $node->id }}) ? '▾' : '▸'"></span>
                            @endif
                            {{ $node->nama }}
                        </td>
                        <td class="px-3 py-2 text-center">
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium {{ $badgeClass }}">
                                {{ $node->jenis }}
                            </span>
                        </td>
                        <td class="px-3 py-2 text-sm text-center text-gray-600">
                            {{ $node->kelas_jabatan ?? '-' }}
                        </td>
                        <td class="px-3 py-2 text-sm text-center text-gray-400 font-mono text-xs">
                            {{ $node->kode ?? '-' }}
                        </td>
                        <td class="px-3 py-2 text-sm text-center">
                            @if($isPosisi)
                            <span class="{{ $pegawaiCount > 0 ? 'text-green-600 font-medium' : 'text-gray-400' }}">
                                {{ $pegawaiCount > 0 ? 'Terisi' : 'Kosong' }}
                            </span>
                            @else
                            <span class="text-gray-300">-</span>
                            @endif
                        </td>
                        <td class="px-3 py-2 text-center">
                            <a href="{{ route('admin.node-organisasi.edit', $node) }}" class="text-blue-600 hover:text-blue-900 text-xs mr-2">Edit</a>
                            <form action="{{ route('admin.node-organisasi.destroy', $node) }}" method="POST" class="inline" onsubmit="return confirm('Hapus node ini?')">
                                @csrf @method('DELETE')
                                <button type="submit" class="text-red-600 hover:text-red-900 text-xs">Hapus</button>
                            </form>
                        </td>
                    </tr>
                    @php
                            if ($hasChildren) {
                                foreach ($childrenMap[$node->id] as $child) {
                                    renderNode($child, $childrenMap, $level + 1, $no);
                                }
                            }
                        }
                    @endphp

                    @php $no = 1; @endphp
                    @if(isset($roots))
                        @foreach($roots as $root)
                            @php renderNode($root, $childrenMap ?? [], 1, $no); @endphp
                        @endforeach
                    @endif

                    @if(!isset($roots) || count($roots ?? []) === 0)
                    <tr>
                        <td colspan="7" class="px-6 py-10 text-center text-gray-500">
                            Belum ada data struktur organisasi.
                            <br>
                            <a href="{{ route('admin.node-organisasi.create') }}" class="text-blue-600 hover:text-blue-800 mt-2 inline-block">
                                + Tambah Unit Organisasi pertama
                            </a>
                        </td>
                    </tr>
                    @endif
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
document.addEventListener('alpine:init', () => {
    Alpine.data('treeData', () => ({
        expandedItems: new Set(Array.from(document.querySelectorAll('tr[data-level="1"]')).map(r => parseInt(r.dataset.id))),

        isVisible(id, parentId) {
            if (parentId === '' || parentId === '0' || parentId === 0) return true;
            return this.expandedItems.has(parseInt(parentId));
        },

        toggleNode(id) {
            if (this.expandedItems.has(id)) {
                this.expandedItems.delete(id);
                this.collapseDescendants(id);
            } else {
                this.expandedItems.add(id);
            }
        },

        isExpanded(id) {
            return this.expandedItems.has(id);
        },

        collapseDescendants(parentId) {
            document.querySelectorAll('tr[data-parent-id="' + parentId + '"]').forEach(row => {
                const childId = parseInt(row.dataset.id);
                this.expandedItems.delete(childId);
                this.collapseDescendants(childId);
            });
        }
    }));
});
</script>
@endsection
