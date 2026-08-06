<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name', 'SIPEKA') }}</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700&display=swap" rel="stylesheet" />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
</head>
<body class="font-sans antialiased bg-gray-100">
    <div class="flex h-screen overflow-hidden">
        <!-- Sidebar -->
        <aside class="w-64 bg-blue-900 text-white flex-shrink-0 hidden lg:flex lg:flex-col">
            <div class="flex items-center justify-center h-16 border-b border-blue-800">
                <a href="{{ route('dashboard') }}" class="text-xl font-bold tracking-wide">SIPEKA</a>
            </div>
            <nav class="flex-1 overflow-y-auto py-4 px-3 space-y-1">
                <a href="{{ route('dashboard') }}" class="flex items-center gap-3 px-3 py-2 rounded-md text-sm font-medium {{ request()->routeIs('dashboard') ? 'bg-blue-800 text-white' : 'text-blue-200 hover:bg-blue-800 hover:text-white' }} transition">
                    <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 6a1 1 0 011-1h3.586a1 1 0 01.707.293l1.414 1.414a1 1 0 00.707.293H19a1 1 0 011 1v9a1 1 0 01-1 1H5a1 1 0 01-1-1V6z"/><rect x="3" y="3" width="7" height="7" rx="1" stroke="currentColor" fill="currentColor" class="opacity-0"/></svg>
                    Dashboard
                </a>
                <a href="{{ route('admin.unor.index') }}" class="flex items-center gap-3 px-3 py-2 rounded-md text-sm font-medium {{ request()->routeIs('admin.unor.*') ? 'bg-blue-800 text-white' : 'text-blue-200 hover:bg-blue-800 hover:text-white' }} transition">
                    <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                    Unit Organisasi
                </a>
                @if(auth()->user()->isAdmin())
                <a href="{{ route('admin.user.index') }}" class="flex items-center gap-3 px-3 py-2 rounded-md text-sm font-medium {{ request()->routeIs('admin.user.*') ? 'bg-blue-800 text-white' : 'text-blue-200 hover:bg-blue-800 hover:text-white' }} transition">
                    <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
                    User
                </a>

                {{-- Referensi — collapsible group --}}
                @php $inReferensi = request()->routeIs('admin.referensi-jabatan.*', 'admin.tugas-tambahan.*'); @endphp
                <div x-data="{ open: {{ $inReferensi ? 'true' : 'false' }} }">
                    <button @click="open = !open"
                            class="flex items-center justify-between w-full px-3 py-2 rounded-md text-sm font-medium transition
                                   {{ $inReferensi ? 'bg-blue-800 text-white' : 'text-blue-200 hover:bg-blue-800 hover:text-white' }}">
                        <span class="flex items-center gap-3">
                            <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                            Referensi</span>
                        <svg :class="open ? 'rotate-90' : ''"
                             class="w-4 h-4 transition-transform duration-200 opacity-70"
                             fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                        </svg>
                    </button>
                    <div x-show="open" x-cloak class="mt-1 space-y-1 border-l border-blue-700 ml-4 pl-3">
                        <a href="{{ route('admin.referensi-jabatan.index') }}" class="flex items-center gap-3 px-3 py-2 rounded-md text-sm font-medium {{ request()->routeIs('admin.referensi-jabatan.*') ? 'bg-blue-800 text-white' : 'text-blue-200 hover:bg-blue-800 hover:text-white' }} transition">
                            Referensi Jabatan
                        </a>
                        <a href="{{ route('admin.tugas-tambahan.index') }}" class="flex items-center gap-3 px-3 py-2 rounded-md text-sm font-medium {{ request()->routeIs('admin.tugas-tambahan.*') ? 'bg-blue-800 text-white' : 'text-blue-200 hover:bg-blue-800 hover:text-white' }} transition">
                            Tugas Tambahan
                        </a>
                    </div>
                </div>
                @endif
                <a href="{{ route('admin.jabatan.index') }}" class="flex items-center gap-3 px-3 py-2 rounded-md text-sm font-medium {{ request()->routeIs('admin.jabatan.*') ? 'bg-blue-800 text-white' : 'text-blue-200 hover:bg-blue-800 hover:text-white' }} transition">
                    <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                    Jabatan
                </a>
                <a href="{{ route('admin.pegawai.index') }}" class="flex items-center gap-3 px-3 py-2 rounded-md text-sm font-medium {{ request()->routeIs('admin.pegawai.*') ? 'bg-blue-800 text-white' : 'text-blue-200 hover:bg-blue-800 hover:text-white' }} transition">
                    <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                    Pegawai
                </a>
                <a href="{{ route('admin.bezetting.index') }}" class="flex items-center gap-3 px-3 py-2 rounded-md text-sm font-medium {{ request()->routeIs('admin.bezetting.*') ? 'bg-blue-800 text-white' : 'text-blue-200 hover:bg-blue-800 hover:text-white' }} transition">
                    <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
                    Bezetting
                </a>
                <a href="{{ route('admin.kebutuhan.index') }}" class="flex items-center gap-3 px-3 py-2 rounded-md text-sm font-medium {{ request()->routeIs('admin.kebutuhan.*') ? 'bg-blue-800 text-white' : 'text-blue-200 hover:bg-blue-800 hover:text-white' }} transition">
                    <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/></svg>
                    Kebutuhan
                </a>
            </nav>
            <div class="border-t border-blue-800 p-3">
                <span class="block px-3 py-1 text-xs text-blue-300 mb-2">
                    {{ auth()->user()->name }}
                    <span class="ml-1 px-1.5 py-0.5 rounded text-xs {{ auth()->user()->isAdmin() ? 'bg-green-700 text-green-100' : 'bg-blue-700 text-blue-100' }}">
                        {{ auth()->user()->isAdmin() ? 'Admin' : 'User' }}
                    </span>
                </span>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="flex items-center gap-3 w-full px-3 py-2 rounded-md text-sm font-medium text-blue-200 hover:bg-blue-800 hover:text-white transition">Logout</button>
                </form>
            </div>
        </aside>

        <!-- Main Content -->
        <div class="flex-1 flex flex-col overflow-hidden">
            <header class="bg-white shadow-sm border-b border-gray-200">
                <div class="flex items-center justify-between h-16 px-6">
                    <h2 class="text-lg font-semibold text-gray-800">SIPEKA - Sistem Perencanaan Kebutuhan ASN Kota Palu</h2>
                </div>
            </header>
            <main class="flex-1 overflow-y-auto bg-gray-100">
                @if(session('success'))
                <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 mt-4">
                    <div class="p-4 bg-green-100 border border-green-400 text-green-700 rounded-md">{{ session('success') }}</div>
                </div>
                @endif
                @if(session('error'))
                <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 mt-4">
                    <div class="p-4 bg-red-100 border border-red-400 text-red-700 rounded-md">{{ session('error') }}</div>
                </div>
                @endif
                @yield('content')
            </main>
        </div>
    </div>
    @yield('scripts')
</body>
</html>
