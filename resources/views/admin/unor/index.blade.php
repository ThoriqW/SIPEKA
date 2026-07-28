@extends('layouts.admin')

@section('content')
<div class="py-6">
    <div class="max-w-full mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex items-center justify-between mb-6">
            <div>
                <h1 class="text-2xl font-semibold text-gray-900">Daftar Unit Organisasi</h1>
                <p class="text-sm text-gray-500 mt-1">Struktur organisasi perangkat daerah</p>
            </div>
            <a href="{{ route('admin.unor.create') }}" class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 text-sm font-medium">+ Tambah Unit Organisasi</a>
        </div>

        <div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase w-12">No</th>
                        <th class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase">Nama Unit Organisasi</th>
                        <th class="px-3 py-3 text-center text-xs font-medium text-gray-500 uppercase w-32">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200" x-data="treeData()" x-cloak>
                    @php $no = 0; @endphp
                    @foreach($tree as $row)
                    @php $no++; @endphp
                    <tr data-id="{{ $row['id'] }}"
                        data-parent-id="{{ $row['parent_id'] }}"
                        data-level="{{ $row['level'] }}"
                        x-show="isVisible('{{ $row['id'] }}', '{{ $row['parent_id'] }}')"
                        @if($row['has_children'])
                        @click="toggleNode('{{ $row['id'] }}')"
                        class="cursor-pointer {{ $row['level'] == 0 ? 'bg-blue-50' : 'hover:bg-gray-50' }}"
                        @else
                        class="{{ $row['level'] == 0 ? 'bg-blue-50' : 'hover:bg-gray-50' }}"
                        @endif
                        >
                        <td class="px-2 py-3 text-sm text-gray-400 text-center w-10">{{ $no }}</td>
                        <td class="py-3 pr-2 text-sm {{ $row['level'] == 0 ? 'font-bold text-gray-900' : ($row['level'] == 1 ? 'font-semibold text-gray-800' : 'text-gray-700') }}"
                            style="padding-left: {{ max(0, $row['level']) * 28 + 8 }}px;">
                            @if($row['has_children'])
                            <span class="text-gray-400 mr-1" x-text="isExpanded('{{ $row['id'] }}') ? '▾' : '▸'"></span>
                            @endif
                            {{ $row['nama'] }}
                        </td>
                        <td class="px-3 py-3 text-sm text-center">
                            <a href="{{ route('admin.unor.edit', $row['unor_id']) }}" class="text-yellow-600 hover:text-yellow-900 mr-2">Edit</a>
                            <form action="{{ route('admin.unor.destroy', $row['unor_id']) }}" method="POST" class="inline" onsubmit="return confirm('Hapus Unit Organisasi ini?')">
                                @csrf @method('DELETE')
                                <button class="text-red-600 hover:text-red-900">Hapus</button>
                            </form>
                        </td>
                    </tr>
                    @endforeach
                    @if(empty($tree))
                    <tr><td colspan="3" class="px-6 py-10 text-center text-gray-500">Tidak ada data Unit Organisasi.</td></tr>
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
        expandedItems: new Set(['u-0']),

        isVisible(id, parentId) {
            if (parentId === '' || parentId === 'u-0') return true;
            return this.expandedItems.has(String(parentId));
        },

        toggleNode(id) {
            if (this.expandedItems.has(String(id))) {
                this.expandedItems.delete(String(id));
                this.collapseDescendants(String(id));
            } else {
                this.expandedItems.add(String(id));
            }
        },

        isExpanded(id) {
            return this.expandedItems.has(String(id));
        },

        collapseDescendants(parentId) {
            document.querySelectorAll('tr[data-parent-id="' + parentId + '"]').forEach(row => {
                const childId = row.dataset.id;
                this.expandedItems.delete(childId);
                this.collapseDescendants(childId);
            });
        }
    }));
});
</script>
@endsection
