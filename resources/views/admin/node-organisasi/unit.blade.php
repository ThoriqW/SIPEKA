@extends('layouts.admin')

@section('content')
<div class="py-6">
    <div class="max-w-full mx-auto px-4 sm:px-6 lg:px-8">
        <div class="sm:flex sm:items-center sm:justify-between mb-6">
            <div>
                <h1 class="text-2xl font-semibold text-gray-900">Unor (Unit Organisasi)</h1>
            </div>
            <a href="{{ route('admin.unor.create') }}" class="mt-3 sm:mt-0 inline-flex items-center px-4 py-2 bg-blue-600 border border-transparent rounded-md font-semibold text-sm text-white hover:bg-blue-700 focus:outline-none transition">
                + Tambah Unor
            </a>
        </div>

        <form method="GET" class="mb-4">
            <input type="text" name="search" value="{{ request('search') }}" placeholder="Cari unit..."
                   class="rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm w-64">
            <button type="submit" class="ml-2 px-3 py-1.5 bg-gray-100 border border-gray-300 rounded-md text-sm hover:bg-gray-200">Cari</button>
        </form>

        <div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider w-10">No</th>
                        <th class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Nama Unor</th>
                        <th class="px-3 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider whitespace-nowrap">Sub-Unor</th>
                        <th class="px-3 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider w-28">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200" x-data="treeData()">
                    @php
                        $no = 1;
                        function renderUnit($node, $childrenMap, $level, &$no) {
                            $indent = ($level - 1) * 28;
                            $hasChildren = isset($childrenMap[$node->id]);
                            $subUnitCount = $hasChildren ? count($childrenMap[$node->id]) : 0;
                    @endphp
                    <tr data-id="{{ $node->id }}"
                        data-parent-id="{{ $node->parent_id ?? '0' }}"
                        data-level="{{ $level }}"
                        x-show="isVisible({{ $node->id }}, '{{ $node->parent_id ?? '0' }}')"
                        @if($hasChildren)
                        @click="toggleNode({{ $node->id }})"
                        class="cursor-pointer {{ $level <= 2 ? 'bg-purple-50/30' : '' }} hover:bg-purple-50/50"
                        @else
                        class="{{ $level <= 2 ? 'bg-purple-50/30' : '' }} hover:bg-purple-50/50"
                        @endif
                    >
                        <td class="px-2 py-2 text-sm text-gray-400 text-center">{{ $no++ }}</td>
                        <td class="py-2 pr-2 text-sm {{ $level <= 1 ? 'font-semibold text-gray-900' : 'text-gray-700' }}"
                            style="padding-left: {{ $indent + 8 }}px;">
                            @if($hasChildren)
                            <span class="text-gray-400 mr-0.5" x-text="isExpanded({{ $node->id }}) ? '▾' : '▸'"></span>
                            @endif
                            <span class="inline-flex items-center px-1.5 py-0.5 rounded text-xs font-medium bg-purple-100 text-purple-800 mr-1">UNOR</span>
                            {{ $node->nama }}
                        </td>
                        <td class="px-3 py-2 text-sm text-center text-gray-600">{{ $subUnitCount }}</td>
                        <td class="px-3 py-2 text-center">
                            <a href="{{ route('admin.unor.edit', $node) }}" class="text-blue-600 hover:text-blue-900 text-xs mr-2">Edit</a>
                            <form action="{{ route('admin.unor.destroy', $node) }}" method="POST" class="inline" onsubmit="return confirm('Hapus unor ini? Hanya bisa dihapus jika tidak memiliki sub-unor atau posisi terisi.')">
                                @csrf @method('DELETE')
                                <button type="submit" class="text-red-600 hover:text-red-900 text-xs">Hapus</button>
                            </form>
                        </td>
                    </tr>
                    @php
                            if ($hasChildren) {
                                foreach ($childrenMap[$node->id] as $child) {
                                    renderUnit($child, $childrenMap, $level + 1, $no);
                                }
                            }
                        }
                    @endphp

                    @if(isset($roots))
                        @foreach($roots as $root)
                            @php renderUnit($root, $childrenMap ?? [], 1, $no); @endphp
                        @endforeach
                    @endif

                    @if(empty($roots ?? []))
                    <tr><td colspan="4" class="px-6 py-10 text-center text-gray-500">Belum ada unor (unit organisasi).</td></tr>
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
        expandedItems: new Set([{{ $rootNode?->id ?? 1 }}]),
        isVisible(id, parentId) {
            if (parentId === '' || parentId === '0' || parentId === 0) return true;
            return this.expandedItems.has(parseInt(parentId));
        },
        toggleNode(id) {
            if (this.expandedItems.has(id)) { this.expandedItems.delete(id); this.collapseDescendants(id); }
            else { this.expandedItems.add(id); }
        },
        isExpanded(id) { return this.expandedItems.has(id); },
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
