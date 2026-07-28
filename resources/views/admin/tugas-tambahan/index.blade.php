@extends('layouts.admin')

@section('content')
<div class="py-6">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex items-center justify-between mb-6">
            <div>
                <h1 class="text-2xl font-semibold text-gray-900">Master Tugas Tambahan</h1>
                <p class="text-sm text-gray-500 mt-1">Kelola referensi tugas tambahan</p>
            </div>
            <a href="{{ route('admin.tugas-tambahan.create') }}" class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 text-sm font-medium">+ Tambah</a>
        </div>

        <div class="bg-white rounded-lg shadow-sm border border-gray-200">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">No</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Nama Tugas</th>
                        <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase w-32">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    @forelse($list as $key => $item)
                    <tr>
                        <td class="px-6 py-4 text-sm text-gray-500">{{ $list->firstItem() + $key }}</td>
                        <td class="px-6 py-4 text-sm text-gray-900">{{ $item->nama_tugas }}</td>
                        <td class="px-6 py-4 text-sm text-center">
                            <a href="{{ route('admin.tugas-tambahan.edit', $item) }}" class="text-yellow-600 hover:text-yellow-900 mr-2">Edit</a>
                            <form action="{{ route('admin.tugas-tambahan.destroy', $item) }}" method="POST" class="inline" onsubmit="return confirm('Hapus tugas tambahan ini?')">
                                @csrf @method('DELETE')
                                <button class="text-red-600 hover:text-red-900">Hapus</button>
                            </form>
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="3" class="px-6 py-10 text-center text-gray-500">Belum ada data.</td></tr>
                    @endforelse
                </tbody>
            </table>
            @if($list->hasPages())<div class="px-6 py-4 border-t">{{ $list->links() }}</div>@endif
        </div>
    </div>
</div>
@endsection
