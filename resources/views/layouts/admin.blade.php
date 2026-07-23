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
                    Dashboard
                </a>
                <a href="{{ route('admin.opd.index') }}" class="flex items-center gap-3 px-3 py-2 rounded-md text-sm font-medium {{ request()->routeIs('admin.opd.*') ? 'bg-blue-800 text-white' : 'text-blue-200 hover:bg-blue-800 hover:text-white' }} transition">
                    OPD
                </a>
                @if(auth()->user()->isBkd())
                <a href="{{ route('admin.user.index') }}" class="flex items-center gap-3 px-3 py-2 rounded-md text-sm font-medium {{ request()->routeIs('admin.user.*') ? 'bg-blue-800 text-white' : 'text-blue-200 hover:bg-blue-800 hover:text-white' }} transition">
                    User
                </a>
                @endif

                {{-- Dropdown: Organisasi & Jabatan --}}
                @php
                    $orgRoutes = ['admin.unor.*', 'admin.kebutuhan-jabatan.*', 'admin.jabatan-asn.*'];
                    $isOrgActive = collect($orgRoutes)->contains(fn($r) => request()->routeIs($r));
                @endphp
                <div x-data="{ open: {{ $isOrgActive ? 'true' : 'false' }} }">
                    <button @click="open = !open"
                            class="flex items-center justify-between w-full px-3 py-2 rounded-md text-sm font-medium {{ $isOrgActive ? 'bg-blue-800 text-white' : 'text-blue-200 hover:bg-blue-800 hover:text-white' }} transition">
                        <span>Organisasi &amp; Jabatan</span>
                        <span x-text="open ? '▾' : '▸'" class="text-xs"></span>
                    </button>
                    <div x-show="open" class="ml-3 mt-1 space-y-1 border-l border-blue-700/50 pl-3">
                        <a href="{{ route('admin.unor.index') }}"
                           class="flex items-center gap-3 px-3 py-1.5 rounded-md text-sm font-medium {{ request()->routeIs('admin.unor.*') ? 'bg-blue-800/70 text-white' : 'text-blue-300 hover:bg-blue-800/50 hover:text-white' }} transition">
                            Unor
                        </a>
                        <a href="{{ route('admin.kebutuhan-jabatan.index') }}"
                           class="flex items-center gap-3 px-3 py-1.5 rounded-md text-sm font-medium {{ request()->routeIs('admin.kebutuhan-jabatan.*') ? 'bg-blue-800/70 text-white' : 'text-blue-300 hover:bg-blue-800/50 hover:text-white' }} transition">
                            Kebutuhan
                        </a>
                        @if(auth()->user()->isBkd())
                        <a href="{{ route('admin.jabatan-asn.index') }}"
                           class="flex items-center gap-3 px-3 py-1.5 rounded-md text-sm font-medium {{ request()->routeIs('admin.jabatan-asn.*') ? 'bg-blue-800/70 text-white' : 'text-blue-300 hover:bg-blue-800/50 hover:text-white' }} transition">
                            Jabatan ASN
                        </a>
                        @endif
                    </div>
                </div>
                <a href="{{ route('admin.pegawai.index') }}" class="flex items-center gap-3 px-3 py-2 rounded-md text-sm font-medium {{ request()->routeIs('admin.pegawai.*') ? 'bg-blue-800 text-white' : 'text-blue-200 hover:bg-blue-800 hover:text-white' }} transition">
                    Pegawai
                </a>
                <a href="{{ route('admin.bezetting.index') }}" class="flex items-center gap-3 px-3 py-2 rounded-md text-sm font-medium {{ request()->routeIs('admin.bezetting.*') ? 'bg-blue-800 text-white' : 'text-blue-200 hover:bg-blue-800 hover:text-white' }} transition">
                    Bezetting
                </a>
                <a href="{{ route('admin.kebutuhan.index') }}" class="flex items-center gap-3 px-3 py-2 rounded-md text-sm font-medium {{ request()->routeIs('admin.kebutuhan.*') ? 'bg-blue-800 text-white' : 'text-blue-200 hover:bg-blue-800 hover:text-white' }} transition">
                    Proyeksi 5 Tahun
                </a>
            </nav>
            <div class="border-t border-blue-800 p-3">
                <span class="block px-3 py-1 text-xs text-blue-300 mb-2">
                    {{ auth()->user()->name }}
                    <span class="ml-1 px-1.5 py-0.5 rounded text-xs {{ auth()->user()->isBkd() ? 'bg-green-700 text-green-100' : 'bg-blue-700 text-blue-100' }}">
                        {{ auth()->user()->isBkd() ? 'Super Admin' : 'User' }}
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
