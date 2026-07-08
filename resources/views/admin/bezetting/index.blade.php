@extends('layouts.admin')

@section('content')
<div class="py-6">
    <div class="max-w-full mx-auto px-4 sm:px-6 lg:px-8">
        <div class="sm:flex sm:items-center sm:justify-between mb-6">
            <div>
                <h1 class="text-2xl font-semibold text-gray-900">Kebutuhan</h1>
                <p class="text-sm text-gray-500 mt-1">Tabel pohon kebutuhan pegawai seluruh OPD</p>
            </div>
            <a href="{{ route('admin.kebutuhan.export') }}" class="mt-3 sm:mt-0 inline-flex items-center px-4 py-2 bg-green-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-green-700 focus:outline-none transition">
                Export Excel
            </a>
        </div>

        <div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-2 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider w-10">No</th>
                        <th class="px-2 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Jabatan</th>
                        <th class="px-2 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider w-14">Kelas</th>
                        <th class="px-2 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider w-16">Keb.</th>
                        <th class="px-2 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider w-16">Bezetting</th>
                        <th class="px-2 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider" colspan="5">Proyeksi Pensiun</th>
                        <th class="px-2 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider" colspan="5">Proyeksi Kebutuhan</th>
                        <th class="px-2 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider w-48">NIP / Nama</th>
                    </tr>
                    <tr>
                        <th></th>
                        <th></th>
                        <th></th>
                        <th></th>
                        <th></th>
                        @for($n = 1; $n <= 5; $n++)
                        <th class="px-2 py-2 text-center text-xs text-gray-400">{{ $tahunLabels[$n] }}</th>
                        @endfor
                        @for($n = 1; $n <= 5; $n++)
                        <th class="px-2 py-2 text-center text-xs text-gray-400">{{ $tahunLabels[$n] }}</th>
                        @endfor
                        <th></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200" x-data="treeData()">
                    @php $no = 0; @endphp
                    @foreach($tree as $row)
                    @php $no++; @endphp
                    <tr data-id="{{ $row['id'] }}"
                        data-parent-id="{{ $row['parent_id'] ?? '' }}"
                        data-level="{{ $row['level'] }}"
                        x-show="isVisible({{ $row['id'] }}, '{{ $row['parent_id'] ?? '' }}')"
                        @if($row['has_children'] && $row['level'] != 0)
                        @click="toggleNode({{ $row['id'] }})"
                        class="{{ $row['level'] == 0 ? 'bg-blue-50' : 'hover:bg-gray-50' }} cursor-pointer"
                        @else
                        class="{{ $row['level'] == 0 ? 'bg-blue-50' : 'hover:bg-gray-50' }}"
                        @endif
                        >
                        <td class="px-2 py-2 text-sm text-gray-400 text-center w-10">
                            {{ $no }}
                        </td>
                        <td class="py-2 pr-2 text-sm {{ $row['level'] == 0 ? 'font-bold text-gray-900' : ($row['level'] == 1 ? 'font-semibold text-gray-800' : 'text-gray-700') }}" style="padding-left: {{ max(0, $row['level'] - 1) * 28 + 8 }}px;">
                            @if($row['has_children'] && $row['level'] != 0)
                            <span class="text-gray-400 mr-0.5" x-text="isExpanded({{ $row['id'] }}) ? '▾' : '▸'"></span>
                            @endif
                            {{ $row['nama_jabatan'] }}
                            @if($row['jenjang'])
                            <span class="text-xs text-gray-400">({{ $row['jenjang'] }})</span>
                            @endif
                        </td>
                        <td class="px-2 py-2 text-sm text-center text-gray-600">{{ $row['kelas_jabatan'] ?? '-' }}</td>
                        <td class="px-2 py-2 text-sm text-center {{ $row['kebutuhan'] === null ? 'text-gray-400' : 'text-gray-900' }}">
                            {{ $row['kebutuhan'] ?? '-' }}
                        </td>
                        <td class="px-2 py-2 text-sm text-center font-medium text-gray-900">{{ $row['bezetting'] }}</td>

                        {{-- Pensiun Proyeksi Thn 1-5 --}}
                        @for($n = 1; $n <= 5; $n++)
                        <td class="px-2 py-2 text-sm text-center {{ ($row['pensiun_proyeksi'][$n] ?? 0) > 0 ? 'text-red-600 font-medium' : 'text-gray-400' }}">
                            {{ $row['pensiun_proyeksi'][$n] ?? 0 }}
                        </td>
                        @endfor

                        {{-- Kebutuhan Proyeksi Thn 1-5 --}}
                        @for($n = 1; $n <= 5; $n++)
                        <td class="px-2 py-2 text-sm text-center {{ ($row['kebutuhan_proyeksi'][$n] ?? 0) > 0 ? 'text-orange-600 font-medium' : 'text-gray-400' }}">
                            {{ $row['kebutuhan_proyeksi'][$n] ?? 0 }}
                        </td>
                        @endfor

                        <td class="px-2 py-2 text-sm text-gray-500">
                            @forelse($row['pegawai_pensiun'] as $peg)
                            <div class="text-xs">{{ $peg['nip'] }} — {{ $peg['nama'] }}</div>
                            @empty
                            <span class="text-xs text-gray-300">-</span>
                            @endforelse
                        </td>
                    </tr>
                    @endforeach
                    @if(empty($tree))
                    <tr>
                        <td colspan="16" class="px-6 py-10 text-center text-gray-500">Tidak ada data.</td>
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
        expandedItems: new Set([0]),

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
