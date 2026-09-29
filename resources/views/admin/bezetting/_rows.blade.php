{{--
    Baris pohon Bezetting.

    Dipakai dua jalur: render awal halaman (buildInitialRows) dan endpoint
    children (buildChildrenRows). Satu definisi baris supaya markup kolom
    tidak pernah menyimpang antara keduanya.

    Mengharapkan: $tree — daftar baris dengan kunci `no` dan `expanded`.
--}}
@foreach($tree as $row)
<tr data-id="{{ $row['id'] }}"
    data-parent-id="{{ $row['parent_id'] ?? '' }}"
    data-level="{{ $row['level'] }}"
    data-type="{{ $row['type'] }}"
    x-show="isVisible('{{ $row['id'] }}', '{{ $row['parent_id'] ?? '' }}')"
    @if($row['has_children'])
    data-children-url="{{ route($childrenRoute, $childrenRouteParams + ['unor' => $row['unor_id']]) }}"
    @click="toggleNode($el)"
    class="cursor-pointer {{ $row['level'] == 0 ? 'bg-blue-50' : ($row['type'] == 'unor' ? 'bg-gray-50 hover:bg-gray-100' : 'hover:bg-gray-50') }}"
    @else
    class="{{ $row['level'] == 0 ? 'bg-blue-50' : 'hover:bg-gray-50' }}"
    @endif
    >
    <td class="px-2 py-2 text-sm text-gray-400 text-center w-10">{{ $row['no'] }}</td>
    <td class="py-2 pr-2 text-sm {{ $row['level'] == 0 ? 'font-bold text-gray-900' : ($row['type'] == 'unor' ? 'font-semibold text-gray-800' : 'text-gray-700') }}"
        style="padding-left: {{ max(0, $row['level'] - 1) * 28 + 8 }}px;">
        @if($row['has_children'] && $row['level'] != 0)
        <span class="text-gray-400 mr-0.5" x-text="isExpanded('{{ $row['id'] }}') ? '▾' : '▸'">{{ ($row['expanded'] ?? false) ? '▾' : '▸' }}</span>
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
