@extends('layouts.admin')

@section('content')
<div class="py-6">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="mb-6">
            <h1 class="text-2xl font-semibold text-gray-900">Tambah Unit Organisasi</h1>
            <p class="text-sm text-gray-500 mt-1"><a href="{{ route('admin.unor.index') }}" class="hover:text-gray-700">Unit Organisasi</a> / Tambah</p>
        </div>

        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6"
             x-data="unorForm({{ $rootUnor ? $rootUnor->id : 'null' }})">
            <form action="{{ route('admin.unor.store') }}" method="POST">
                @csrf
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Unit Organisasi</label>
                        <input type="text" name="nama_unor" value="{{ old('nama_unor') }}"
                               class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 @error('nama_unor') border-red-500 @enderror">
                        @error('nama_unor')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                    </div>

                    {{-- Unit Organisasi Induk — searchable dropdown --}}
                    <div x-data="{ open: false, search: '', selectedText: '{{ old('parent_id') ? $parentList[old('parent_id')] ?? '' : '' }}' }"
                         class="relative">
                        <input type="hidden" name="parent_id" x-ref="parentId" value="{{ old('parent_id') }}">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Unit Organisasi Induk</label>
                        <div class="relative">
                            <input type="text" x-model="search" x-ref="searchInput"
                                   @focus="open = true" @click="open = true"
                                   @input="open = true"
                                   @blur="setTimeout(() => { open = false; if (search && search !== selectedText) search = '' }, 150)"
                                   :placeholder="selectedText || 'Cari Unit Organisasi Induk'"
                                   class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 pr-8">
                            <span class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 pointer-events-none"
                                  @click.stop="$refs.searchInput.focus(); open = true">▾</span>
                        </div>
                        <div x-show="open" x-cloak
                             @mousedown.prevent
                             class="absolute z-50 mt-1 w-full bg-white border border-gray-300 rounded-md shadow-lg max-h-48 overflow-y-auto">
                            @foreach($parentList as $id => $nama)
                            <div @click="open = false; search = ''; selectedText = '{{ $nama }}'; $refs.parentId.value = '{{ $id }}'; onParentChange({{ $id }})"
                                 x-show="!search || '{{ strtolower($nama) }}'.includes(search.toLowerCase())"
                                 class="px-3 py-2 text-sm hover:bg-blue-50 cursor-pointer {{ old('parent_id') == $id ? 'bg-blue-100' : '' }}">{{ $nama }}</div>
                            @endforeach
                        </div>
                        @error('parent_id')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                    </div>

                    {{-- Singkatan — hanya untuk OPD-level (induk = root) --}}
                    <div x-show="showSingkatan" x-cloak>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Singkatan</label>
                        <input type="text" name="singkatan" value="{{ old('singkatan') }}" maxlength="10"
                               placeholder="Contoh: BKPSDMD"
                               class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 @error('singkatan') border-red-500 @enderror">
                        @error('singkatan')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                    </div>
                </div>
                <div class="flex gap-3 mt-6">
                    <a href="{{ route('admin.unor.index') }}" class="px-4 py-2 bg-gray-200 text-gray-700 rounded-md hover:bg-gray-300 text-sm">Kembali</a>
                    <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 text-sm">Simpan</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
function unorForm(rootUnorId) {
    return {
        showSingkatan: {{ old('parent_id') && old('parent_id') == ($rootUnor ? $rootUnor->id : null) ? 'true' : 'false' }},
        onParentChange(parentId) {
            this.showSingkatan = (parentId == rootUnorId);
        }
    }
}
</script>
@append
