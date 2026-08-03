@extends('layouts.admin')

@section('content')
<div class="py-6">
    <div class="max-w-full mx-auto px-4 sm:px-6 lg:px-8">
        <div class="sm:flex sm:items-center sm:justify-between mb-6">
            <div>
                <h1 class="text-2xl font-semibold text-gray-900">Kebutuhan</h1>
                <p class="text-sm text-gray-500 mt-1">Tabel pohon kebutuhan pegawai — Unit Organisasi & Jabatan</p>
            </div>
            <a href="{{ route('admin.kebutuhan.export') }}" class="px-4 py-2 bg-green-600 text-white rounded-md hover:bg-green-700 text-sm font-medium">
                Export Excel
            </a>
        </div>

        <div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-2 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider w-10">No</th>
                        <th class="px-2 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Nama</th>
                        <th class="px-2 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider w-14">Kelas</th>
                        <th class="px-2 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider w-16">Keb.</th>
                        <th class="px-2 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider w-16">Bezetting</th>
                        <th class="px-2 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider bg-red-50" colspan="5">Proyeksi Pensiun</th>
                        <th class="px-2 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider bg-amber-50" colspan="5">Proyeksi Kebutuhan</th>
                        <th class="px-2 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider w-48">NIP / Nama</th>
                    </tr>
                    <tr>
                        <th></th><th></th><th></th><th></th><th></th>
                        @foreach($tahunLabels as $n => $tahun)
                        <th class="px-2 py-2 text-center text-xs text-gray-400 bg-red-50">{{ $tahun }}</th>
                        @endforeach
                        @foreach($tahunLabels as $n => $tahun)
                        <th class="px-2 py-2 text-center text-xs text-gray-400 bg-amber-50">{{ $tahun }}</th>
                        @endforeach
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
                        data-type="{{ $row['type'] }}"
                        x-show="isVisible('{{ $row['id'] }}', '{{ $row['parent_id'] ?? '' }}')"
                        @if($row['has_children'])
                        @click="toggleNode('{{ $row['id'] }}')"
                        class="cursor-pointer {{ $row['level'] == 0 ? 'bg-blue-50' : ($row['type'] == 'unor' ? 'bg-gray-50 hover:bg-gray-100' : 'hover:bg-gray-50') }}"
                        @else
                        class="{{ $row['level'] == 0 ? 'bg-blue-50' : 'hover:bg-gray-50' }}"
                        @endif
                        >
                        <td class="px-2 py-2 text-sm text-gray-400 text-center w-10">{{ $no }}</td>
                        <td class="py-2 pr-2 text-sm {{ $row['level'] == 0 ? 'font-bold text-gray-900' : ($row['type'] == 'unor' ? 'font-semibold text-gray-800' : 'text-gray-700') }}"
                            style="padding-left: {{ max(0, $row['level'] - 1) * 28 + 8 }}px;">
                            @if($row['has_children'] && $row['level'] != 0)
                            <span class="text-gray-400 mr-0.5" x-text="isExpanded('{{ $row['id'] }}') ? '▾' : '▸'"></span>
                            @endif
                            {{ $row['nama_jabatan'] }}
                            @if($row['type'] == 'unor')
                            <span class="text-xs text-blue-500 ml-1">[Unit Organisasi]</span>
                            @endif
                            @if($row['jenjang'])
                            <span class="text-xs text-gray-400">({{ $row['jenjang'] }})</span>
                            @endif
                        </td>
                        <td class="px-2 py-2 text-sm text-center text-gray-600">{{ $row['kelas_jabatan'] ?? '-' }}</td>
                        <td class="px-2 py-2 text-sm text-center {{ $row['type'] == 'unor' ? 'font-medium text-blue-600' : 'text-gray-900' }}">{{ $row['kebutuhan'] ?? 0 }}</td>
                        <td class="px-2 py-2 text-sm text-center font-medium {{ $row['type'] == 'unor' ? 'text-blue-600' : 'text-gray-900' }}">{{ $row['bezetting'] ?? 0 }}</td>
                        @foreach($row['pensiun_proyeksi'] ?? [] as $val)
                        <td class="px-2 py-2 text-sm text-center bg-red-50 {{ $val > 0 ? 'text-red-600 font-medium' : 'text-gray-400' }}">{{ $val }}</td>
                        @endforeach
                        @foreach($row['kebutuhan_proyeksi'] ?? [] as $val)
                        <td class="px-2 py-2 text-sm text-center bg-amber-50 {{ $val > 0 ? 'text-orange-600 font-medium' : 'text-gray-400' }}">{{ $val }}</td>
                        @endforeach
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
                    <tr><td colspan="16" class="px-6 py-10 text-center text-gray-500">Tidak ada data.</td></tr>
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
        expandedItems: new Set([@json($tree[0]['id'] ?? 'u-0')]),
        isVisible(id, parentId) {
            if (parentId === '' || parentId === 'u-0' || parentId === '0' || parentId === 0) return true;
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
        isExpanded(id) { return this.expandedItems.has(String(id)); },
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
