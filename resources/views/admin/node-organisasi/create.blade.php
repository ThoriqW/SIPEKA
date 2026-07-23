@extends('layouts.admin')

@section('content')
<div class="py-6">
    <div class="max-w-2xl mx-auto px-4 sm:px-6 lg:px-8">
        @php
            $isUnor = $isUnor ?? request()->routeIs('admin.unor.*');
            $title = $isUnor ? 'Tambah Unor (Unit Organisasi)' : 'Tambah Posisi (Kebutuhan Jabatan)';
            $defaultJenis = $isUnor ? 'UNIT' : 'POSISI';
        @endphp
        <h1 class="text-2xl font-semibold text-gray-900 mb-6">{{ $title }}</h1>

        <form action="{{ route($storeRoute ?? 'admin.unor.store') }}" method="POST" class="bg-white rounded-lg shadow-sm border border-gray-200 p-6 space-y-4">
            @csrf
            <input type="hidden" name="jenis" value="{{ old('jenis', $defaultJenis) }}" id="jenis">

            <!-- Nama -->
            <div>
                <label class="block text-sm font-medium text-gray-700">Nama</label>
                <input type="text" name="nama" value="{{ old('nama') }}" required
                       class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm">
                @error('nama') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
            </div>

            <!-- Induk (cascading: OPD → Unor) -->
            <div x-data="indukCascading()">
                <!-- Pilih OPD -->
                <div class="mb-3">
                    <label class="block text-sm font-medium text-gray-700">OPD <span class="text-red-500">*</span></label>
                    <select x-model="selectedOpd" @change="loadInduk()"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm">
                        <option value="">-- Pilih OPD --</option>
                        @foreach($opdList as $opd)
                        <option value="{{ $opd['id'] }}">{{ $opd['nama'] }}</option>
                        @endforeach
                    </select>
                </div>

                <!-- Pilih Induk (dimuat via AJAX) -->
                <div>
                    <label class="block text-sm font-medium text-gray-700">Unor Atasan <span class="text-red-500">*</span></label>
                    <select name="parent_id" required x-ref="indukSelect"
                            :disabled="!selectedOpd"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm">
                        <option value="">-- Pilih Unor Atasan --</option>
                        <template x-for="u in indukList" :key="u.id">
                            <option :value="u.id" x-text="u.nama"></option>
                        </template>
                    </select>
                    @error('parent_id') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                    <p class="text-xs text-red-500 mt-1" x-show="errorMessage" x-text="errorMessage"></p>
                    <p class="text-xs text-amber-600 mt-1" x-show="selectedOpd && indukList.length === 0 && !errorMessage">
                        OPD ini belum memiliki sub-Unor. Silakan pilih OPD lain.
                    </p>
                </div>
            </div>

            {{-- Kelas Jabatan — hanya untuk POSISI --}}
            @if(!$isUnor)
            <div>
                <label class="block text-sm font-medium text-gray-700">Kelas Jabatan</label>
                <input type="number" name="kelas_jabatan" value="{{ old('kelas_jabatan') }}" min="1"
                       class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm w-32"
                       placeholder="Misal: 7">
                <p class="text-xs text-gray-400 mt-1">Tingkat kelas jabatan untuk posisi ini.</p>
                @error('kelas_jabatan') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
            </div>
            @endif

            <div class="flex items-center gap-3 pt-4">
                <button type="submit" class="px-4 py-2 bg-blue-600 border border-transparent rounded-md font-semibold text-sm text-white hover:bg-blue-700">
                    Simpan
                </button>
                <a href="{{ $isUnor ? route('admin.unor.index') : route('admin.kebutuhan-jabatan.index') }}" class="px-4 py-2 bg-gray-200 border border-gray-300 rounded-md font-semibold text-sm text-gray-700 hover:bg-gray-300">
                    Batal
                </a>
            </div>
        </form>
    </div>
</div>
@endsection

@section('scripts')
<script>
document.addEventListener('alpine:init', () => {
    Alpine.data('indukCascading', () => ({
        selectedOpd: '',
        indukList: [],
        errorMessage: '',

        async loadInduk() {
            this.errorMessage = '';
            if (!this.selectedOpd) {
                this.indukList = [];
                return;
            }
            try {
                const res = await fetch(`/admin/unor/ajax/by-opd?opd_id=${this.selectedOpd}`);
                const json = await res.json();
                this.indukList = json.data || [];
            } catch (e) {
                this.indukList = [];
                this.errorMessage = 'Gagal memuat data. Coba lagi.';
            }
        },

        init() {
            @if(old('parent_id'))
            // Jika ada old value, pre-load induk list
            this.loadInduk();
            @endif
        }
    }));
});
</script>
@endsection
