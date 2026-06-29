@extends('layouts.admin')

@section('content')
<div class="py-6">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="mb-6">
            <h1 class="text-2xl font-semibold text-gray-900">Detail OPD</h1>
            <p class="text-sm text-gray-500 mt-1"><a href="{{ route('admin.opd.index') }}" class="hover:text-gray-700">OPD</a> / {{ $opd->nama_opd }}</p>
        </div>
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6 mb-6">
            <h2 class="text-lg font-semibold mb-4">Informasi OPD</h2>
            <table class="w-full">
                <tr class="border-b"><td class="py-3 pr-4 text-sm font-medium text-gray-500 w-48">Nama OPD</td><td class="py-3 text-sm">{{ $opd->nama_opd }}</td></tr>
                <tr class="border-b"><td class="py-3 pr-4 text-sm font-medium text-gray-500">Kode OPD</td><td class="py-3 text-sm">{{ $opd->kode_opd }}</td></tr>
            </table>
        </div>
        <div class="bg-white rounded-lg shadow-sm border border-gray-200">
            <div class="px-6 py-4 border-b"><h2 class="text-lg font-semibold">Daftar Jabatan Struktural</h2></div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50"><tr><th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">No</th><th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Kode</th><th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Nama Jabatan</th></tr></thead>
                    <tbody class="divide-y divide-gray-200">
                        @forelse($opd->jabatan as $key => $jabatan)
                        <tr><td class="px-6 py-4 text-sm text-gray-500">{{ $key + 1 }}</td><td class="px-6 py-4 text-sm text-gray-500">{{ $jabatan->kode_jabatan }}</td><td class="px-6 py-4 text-sm font-medium">{{ $jabatan->nama_jabatan }}</td></tr>
                        @empty
                        <tr><td colspan="3" class="px-6 py-10 text-center text-gray-500">Tidak ada jabatan struktural.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        <div class="mt-6"><a href="{{ route('admin.opd.index') }}" class="px-4 py-2 bg-gray-200 text-gray-700 rounded-md hover:bg-gray-300 text-sm">Kembali</a></div>
    </div>
</div>
@endsection
