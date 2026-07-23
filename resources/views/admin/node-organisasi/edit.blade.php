@extends('layouts.admin')

@section('content')
<div class="py-6">
    <div class="max-w-2xl mx-auto px-4 sm:px-6 lg:px-8">
        @php
            $isUnor = $node->isUnit();
            $title = $isUnor ? 'Edit Unor (Unit Organisasi)' : 'Edit Posisi (Kebutuhan Jabatan)';
        @endphp
        <h1 class="text-2xl font-semibold text-gray-900 mb-6">{{ $title }}</h1>

        <form action="{{ route($updateRoute ?? 'admin.unor.update', $node) }}" method="POST" class="bg-white rounded-lg shadow-sm border border-gray-200 p-6 space-y-4">
            @csrf @method('PUT')

            <!-- Read-only info -->
            <div class="bg-gray-50 rounded-md p-3 text-sm text-gray-600 space-y-1 mb-4">
                <div><strong>Kode:</strong> {{ $node->kode ?? '(auto-generated)' }}</div>
                <div><strong>Jenis:</strong>
                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium {{ $isUnor ? 'bg-purple-100 text-purple-800' : 'bg-blue-100 text-blue-800' }}">
                        {{ $isUnor ? 'UNOR' : 'POSISI' }}
                    </span>
                </div>
                @if($node->isTerisi())
                <div class="text-amber-600">⚠ Posisi ini sedang diisi pegawai. Perubahan jenis ke UNIT tidak diizinkan.</div>
                @endif
            </div>

            <!-- Nama -->
            <div>
                <label class="block text-sm font-medium text-gray-700">Nama</label>
                <input type="text" name="nama" value="{{ old('nama', $node->nama) }}" required
                       class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm">
                @error('nama') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
            </div>

            <!-- Jenis (hidden — tidak bisa diubah via form ini) -->
            <input type="hidden" name="jenis" value="{{ $node->jenis }}">

            <!-- Induk -->
            <div>
                <label class="block text-sm font-medium text-gray-700">Induk <span class="text-red-500">*</span></label>
                <select name="parent_id" required
                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm">
                    <option value="">-- Pilih Induk --</option>
                    @foreach($parentOptions as $opt)
                    <option value="{{ $opt['id'] }}" {{ old('parent_id', $node->parent_id) == $opt['id'] ? 'selected' : '' }}>{{ $opt['nama'] }}</option>
                    @endforeach
                </select>
                @error('parent_id') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
            </div>

            {{-- Kelas Jabatan — hanya untuk POSISI --}}
            @if(!$isUnor)
            <div>
                <label class="block text-sm font-medium text-gray-700">Kelas Jabatan</label>
                <input type="number" name="kelas_jabatan" value="{{ old('kelas_jabatan', $node->kelas_jabatan) }}" min="1"
                       class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm w-32">
                @error('kelas_jabatan') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
            </div>
            @endif

            <div class="flex items-center gap-3 pt-4">
                <button type="submit" class="px-4 py-2 bg-blue-600 border border-transparent rounded-md font-semibold text-sm text-white hover:bg-blue-700">
                    Perbarui
                </button>
                <a href="{{ $isUnor ? route('admin.unor.index') : route('admin.kebutuhan-jabatan.index') }}" class="px-4 py-2 bg-gray-200 border border-gray-300 rounded-md font-semibold text-sm text-gray-700 hover:bg-gray-300">
                    Batal
                </a>
            </div>
        </form>
    </div>
</div>
@endsection
