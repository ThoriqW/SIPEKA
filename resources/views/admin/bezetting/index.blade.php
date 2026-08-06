@extends('layouts.admin')

@section('content')
<div class="py-6">
    <div class="max-w-full mx-auto px-4 sm:px-6 lg:px-8">
        <div class="sm:flex sm:items-center sm:justify-between mb-6">
            <div>
                <h1 class="text-2xl font-semibold text-gray-900">Bezetting</h1>
                <p class="text-sm text-gray-500 mt-1">Tabel pohon bezetting pegawai — Unit Organisasi & Jabatan</p>
            </div>
            <a href="{{ route('admin.bezetting.export', request()->query()) }}" class="px-4 py-2 bg-green-600 text-white rounded-md hover:bg-green-700 text-sm font-medium">
                Export Excel
            </a>
        </div>

        @if($opdList->isNotEmpty())
        <form method="GET" class="mb-4">
            <div class="flex items-center gap-3">
                <label for="unor_id" class="text-sm font-medium text-gray-700">OPD:</label>
                <select id="unor_id" name="unor_id" x-on:change="$el.form.submit()"
                        class="rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm w-72">
                    <option value="">-- Semua OPD --</option>
                    @foreach($opdList as $id => $nama)
                    <option value="{{ $id }}" {{ request('unor_id') == $id ? 'selected' : '' }}>{{ $nama }}</option>
                    @endforeach
                </select>
            </div>
        </form>
        @endif

        <div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider w-12">No</th>
                        <th class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Nama</th>
                        <th class="px-3 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider w-16">Kelas</th>
                        <th class="px-3 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider w-20">Kebutuhan</th>
                        <th class="px-3 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider w-20">Bezetting</th>
                        <th class="px-3 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider w-20">Selisih</th>
                        <th class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider w-48">NIP / Nama</th>
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
                        <td class="px-3 py-2 text-sm text-center text-gray-600">{{ $row['kelas_jabatan'] ?? '-' }}</td>
                        <td class="px-3 py-2 text-sm text-center {{ $row['type'] == 'unor' ? 'font-medium text-blue-600' : 'text-gray-900' }}">{{ $row['kebutuhan'] ?? 0 }}</td>
                        <td class="px-3 py-2 text-sm text-center font-medium {{ $row['type'] == 'unor' ? 'text-blue-600' : 'text-gray-900' }}">{{ $row['bezetting'] ?? 0 }}</td>
                        <td class="px-3 py-2 text-sm text-center font-medium {{ ($row['selisih'] ?? 0) < 0 ? 'text-red-600' : (($row['selisih'] ?? 0) > 0 ? 'text-green-600' : 'text-gray-500') }}">{{ $row['selisih'] ?? 0 }}</td>
                        <td class="px-3 py-2 text-sm text-gray-500">
                            @forelse($row['pegawai'] as $peg)
                            <div class="text-xs">
                                {{ $peg['nip'] }} — {{ $peg['nama'] }}
                                @if(!empty($peg['tugas_tambahan']))
                                @foreach($peg['tugas_tambahan'] as $namaTugas)
                                <span class="inline-block ml-1 px-1 py-0 text-[10px] leading-tight rounded-full bg-purple-100 text-purple-700 font-normal">{{ $namaTugas }}</span>
                                @endforeach
                                @endif
                            </div>
                            @empty
                            <span class="text-xs text-gray-300">-</span>
                            @endforelse
                        </td>
                    </tr>
                    @endforeach
                    @if(empty($tree))
                    <tr><td colspan="7" class="px-6 py-10 text-center text-gray-500">Tidak ada data.</td></tr>
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
