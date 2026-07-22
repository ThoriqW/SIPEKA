@extends('layouts.admin')

@section('content')
<div class="py-6">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex items-center justify-between mb-6">
            <div>
                <h1 class="text-2xl font-semibold text-gray-900">Daftar User</h1>
                <p class="text-sm text-gray-500 mt-1">Kelola akun user yang dapat login ke sistem</p>
            </div>
            <a href="{{ route('admin.user.create') }}" class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 text-sm font-medium">+ Tambah User</a>
        </div>

        <div class="bg-white rounded-lg shadow-sm border border-gray-200">
            <div class="p-4 border-b">
                <form method="GET" class="flex gap-4">
                    <input type="text" name="search" placeholder="Cari NIP atau nama..." value="{{ request('search') }}" class="flex-1 rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    <button type="submit" class="px-4 py-2 bg-gray-600 text-white rounded-md hover:bg-gray-700 text-sm">Cari</button>
                    @if(request('search'))<a href="{{ route('admin.user.index') }}" class="px-4 py-2 bg-gray-200 text-gray-700 rounded-md hover:bg-gray-300 text-sm">Reset</a>@endif
                </form>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">No</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">NIP</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Nama</th>
                            <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase">Role</th>
                            <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase">Status</th>
                            <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        @forelse($userList as $key => $user)
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-4 text-sm text-gray-500">{{ $userList->firstItem() + $key }}</td>
                            <td class="px-6 py-4 text-sm font-medium text-gray-900">{{ $user->nip ?? '—' }}</td>
                            <td class="px-6 py-4 text-sm text-gray-900">
                                {{ $user->name }}
                                @if($user->pegawai)
                                    <div class="text-xs text-gray-500">{{ $user->pegawai->opd->nama_opd ?? '—' }}</div>
                                @endif
                            </td>
                            <td class="px-6 py-4 text-sm text-center">
                                <span class="px-2 py-1 rounded text-xs font-medium {{ $user->isBkd() ? 'bg-green-100 text-green-800' : 'bg-blue-100 text-blue-800' }}">
                                    {{ $user->isBkd() ? 'Super Admin' : 'User' }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-sm text-center">
                                <span class="px-2 py-1 rounded text-xs font-medium {{ $user->isActive() ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                                    {{ $user->isActive() ? 'Aktif' : 'Nonaktif' }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-sm text-center">
                                <a href="{{ route('admin.user.edit', $user) }}" class="text-yellow-600 hover:text-yellow-900 mr-2">Edit</a>
                                @if($user->id !== auth()->id())
                                <form action="{{ route('admin.user.destroy', $user) }}" method="POST" class="inline" onsubmit="return confirm('Hapus user ini? Tindakan ini tidak dapat dibatalkan.')">
                                    @csrf @method('DELETE')
                                    <button class="text-red-600 hover:text-red-900">Hapus</button>
                                </form>
                                @endif
                            </td>
                        </tr>
                        @empty
                        <tr><td colspan="6" class="px-6 py-10 text-center text-gray-500">Tidak ada data user.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if($userList->hasPages())<div class="px-6 py-4 border-t">{{ $userList->links() }}</div>@endif
        </div>
    </div>
</div>
@endsection
