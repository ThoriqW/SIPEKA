@extends('layouts.admin')

@section('content')
<div class="py-6">
    <div class="max-w-full mx-auto px-4 sm:px-6 lg:px-8">
        <div class="sm:flex sm:items-center sm:justify-between mb-6">
            <div>
                <h1 class="text-2xl font-semibold text-gray-900">Bezetting</h1>
                <p class="text-sm text-gray-500 mt-1">Unit Organisasi & Jabatan</p>
            </div>
            <a href="{{ route('admin.bezetting.export', request()->query()) }}" class="px-4 py-2 bg-green-600 text-white rounded-md hover:bg-green-700 text-sm font-medium">
                Export Excel
            </a>
        </div>

        @if($opdList->isNotEmpty())
        <form method="GET" class="mb-4" x-data>
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
                <tbody class="divide-y divide-gray-200"
                       data-colspan="{{ $colspan }}"
                       x-data="treeData()">
                    @include('admin.bezetting._rows', ['tree' => $tree])
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
@include('admin.partials._tree-script')
@endsection
