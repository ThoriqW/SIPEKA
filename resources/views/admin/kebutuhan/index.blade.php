@extends('layouts.admin')

@section('content')
<div class="py-6">
    <div class="max-w-full mx-auto px-4 sm:px-6 lg:px-8">
        <div class="sm:flex sm:items-center sm:justify-between mb-6">
            <div>
                <h1 class="text-2xl font-semibold text-gray-900">Kebutuhan</h1>
                <p class="text-sm text-gray-500 mt-1">Unit Organisasi & Jabatan</p>
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
                <tbody class="divide-y divide-gray-200"
                       data-colspan="{{ $colspan }}"
                       x-data="treeData()">
                    @include('admin.kebutuhan._rows', ['tree' => $tree])
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
@include('admin.partials._tree-script')
@endsection
