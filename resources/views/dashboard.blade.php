@extends('layouts.admin')

@section('content')
<div class="py-6">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="mb-6">
            <h1 class="text-2xl font-semibold text-gray-900">Dashboard</h1>
            <p class="text-sm text-gray-500 mt-1">Selamat datang, {{ auth()->user()->name }}</p>
        </div>

        <!-- Stat Cards -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-500">Total Pegawai</p>
                        <p class="text-3xl font-bold text-gray-900 mt-1">{{ number_format($totalPegawai ?? 0) }}</p>
                    </div>
                    <div class="w-12 h-12 bg-blue-100 rounded-full flex items-center justify-center text-blue-600 font-bold">P</div>
                </div>
            </div>
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-500">PNS</p>
                        <p class="text-3xl font-bold text-gray-900 mt-1">{{ number_format($totalPns ?? 0) }}</p>
                    </div>
                    <div class="w-12 h-12 bg-green-100 rounded-full flex items-center justify-center text-green-600 font-bold">P</div>
                </div>
            </div>
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-500">PPPK</p>
                        <p class="text-3xl font-bold text-gray-900 mt-1">{{ number_format($totalPppk ?? 0) }}</p>
                    </div>
                    <div class="w-12 h-12 bg-yellow-100 rounded-full flex items-center justify-center text-yellow-600 font-bold">K</div>
                </div>
            </div>
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-500">Total Perangkat Daerah</p>
                        <p class="text-3xl font-bold text-gray-900 mt-1">{{ number_format($totalOpd ?? 0) }}</p>
                    </div>
                    <div class="w-12 h-12 bg-purple-100 rounded-full flex items-center justify-center text-purple-600 font-bold">O</div>
                </div>
            </div>
        </div>

        {{-- Total Kebutuhan --}}
        <div class="bg-gradient-to-r from-blue-600 to-blue-700 rounded-lg shadow-sm p-6 mb-8 text-white">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-blue-100">Total Kebutuhan Pegawai</p>
                    <p class="text-4xl font-bold mt-1">{{ number_format($totalKebutuhan ?? 0) }}</p>
                </div>
                <div class="w-16 h-16 bg-white/20 rounded-full flex items-center justify-center">
                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
                </div>
            </div>
            <p class="text-sm text-blue-200 mt-2">Akumulasi kebutuhan dari seluruh jabatan Struktural, Fungsional, dan Pelaksana</p>
        </div>

        {{-- Pegawai per Jenis Jabatan — 2 kolom --}}
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
            {{-- Struktural --}}
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                <div class="flex items-center gap-3 mb-4">
                    <div class="w-10 h-10 bg-purple-100 rounded-full flex items-center justify-center text-purple-600 font-bold text-sm">S</div>
                    <div>
                        <h3 class="text-sm font-semibold text-gray-800">Pegawai Struktural</h3>
                        <p class="text-2xl font-bold text-purple-600">{{ number_format(($pegawaiPerJenisJenjang['Struktural'] ?? collect())->sum('total')) }}</p>
                    </div>
                </div>
                @php $data = $pegawaiPerJenisJenjang['Struktural'] ?? collect(); @endphp
                @if($data->isNotEmpty())
                <table class="w-full text-sm"><tbody class="divide-y divide-gray-100">
                    @foreach($data as $row)
                    <tr><td class="py-1.5 text-gray-600">{{ $row->jenjang ?: 'Tanpa Jenjang' }}</td><td class="py-1.5 text-right font-medium text-gray-900">{{ number_format($row->total) }}</td></tr>
                    @endforeach
                </tbody></table>
                @else
                <p class="text-sm text-gray-400">Belum ada data</p>
                @endif
            </div>

            {{-- Fungsional & Pelaksana --}}
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                <div class="flex items-center gap-3 mb-4">
                    <div class="w-10 h-10 bg-blue-100 rounded-full flex items-center justify-center text-blue-600 font-bold text-sm">F</div>
                    <div>
                        <h3 class="text-sm font-semibold text-gray-800">Pegawai Fungsional & Pelaksana</h3>
                        <p class="text-2xl font-bold text-blue-600">{{ number_format(($pegawaiPerJenisJenjang['Fungsional'] ?? collect())->sum('total') + ($pegawaiPerJenisJenjang['Pelaksana'] ?? collect())->sum('total')) }}</p>
                    </div>
                </div>
                <table class="w-full text-sm"><tbody class="divide-y divide-gray-100">
                    @foreach(['Guru', 'Kesehatan', 'Non Guru & Non Kesehatan'] as $label)
                    <tr>
                        <td class="py-2 text-gray-600">{{ $label }}</td>
                        <td class="py-2 text-right font-medium text-gray-900">{{ number_format($pegawaiFungsionalPerGroup[$label] ?? 0) }}</td>
                    </tr>
                    @endforeach
                    @php $totalPelaksana = ($pegawaiPerJenisJenjang['Pelaksana'] ?? collect())->sum('total'); @endphp
                    <tr>
                        <td class="py-2 text-gray-600">Pelaksana</td>
                        <td class="py-2 text-right font-medium text-gray-900">{{ number_format($totalPelaksana) }}</td>
                    </tr>
                </tbody></table>
            </div>
        </div>

    </div>
</div>
@endsection
