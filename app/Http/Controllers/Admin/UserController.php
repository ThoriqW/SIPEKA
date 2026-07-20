<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Pegawai;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    /**
     * Daftar semua user.
     */
    public function index(Request $request)
    {
        $query = User::query()->with('pegawai');

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('nip', 'like', "%{$search}%")
                  ->orWhere('name', 'like', "%{$search}%")
                  ->orWhereHas('pegawai', fn($pq) => $pq->where('nama', 'like', "%{$search}%"));
            });
        }

        $userList = $query->orderBy('name')->paginate(15)->withQueryString();

        return view('admin.user.index', compact('userList'));
    }

    /**
     * Form tambah user.
     */
    public function create()
    {
        // Ambil pegawai yang belum punya akun user
        $usedNips = User::whereNotNull('nip')->pluck('nip')->toArray();
        $pegawaiList = Pegawai::whereNotIn('nip', $usedNips)
            ->orderBy('nama')
            ->get();

        return view('admin.user.create', compact('pegawaiList'));
    }

    /**
     * Simpan user baru.
     */
    public function store(Request $request)
    {
        $rules = [
            'password' => 'required|string|min:6|confirmed',
            'role' => 'required|in:bkd,user',
        ];

        if ($request->role === 'user') {
            // User biasa: NIP harus dari pegawai existing
            $rules['nip'] = [
                'required', 'string', 'size:18',
                Rule::unique('users', 'nip')->whereNotNull('nip'),
                'exists:pegawai,nip',
            ];
            $rules['username'] = 'nullable';
        } else {
            // Super admin: bisa dengan username custom
            $rules['username'] = 'required|string|max:255|unique:users,nip';
            $rules['nip'] = 'nullable';
        }

        $validated = $request->validate($rules);

        if ($validated['role'] === 'user') {
            $pegawai = Pegawai::where('nip', $validated['nip'])->first();
            $name = $pegawai->nama;
            $nip = $validated['nip'];
        } else {
            $name = 'Admin ' . $validated['username'];
            $nip = $validated['username'];
        }

        User::create([
            'name' => $name,
            'nip' => $nip,
            'email' => null,
            'password' => Hash::make($validated['password']),
            'role' => $validated['role'],
            'is_active' => true,
        ]);

        return redirect()->route('admin.user.index')
            ->with('success', 'User berhasil ditambahkan.');
    }

    /**
     * Form edit user.
     */
    public function edit(User $user)
    {
        $pegawai = $user->pegawai;
        return view('admin.user.edit', compact('user', 'pegawai'));
    }

    /**
     * Update user.
     */
    public function update(Request $request, User $user)
    {
        $validated = $request->validate([
            'password' => 'nullable|string|min:6|confirmed',
            'role' => 'required|in:bkd,user',
            'is_active' => 'boolean',
        ]);

        $data = [
            'role' => $validated['role'],
            'is_active' => $request->boolean('is_active'),
        ];

        // Hanya update password jika diisi
        if (!empty($validated['password'])) {
            $data['password'] = Hash::make($validated['password']);
        }

        $user->update($data);

        return redirect()->route('admin.user.index')
            ->with('success', 'User berhasil diperbarui.');
    }

    /**
     * Hapus user.
     */
    public function destroy(User $user)
    {
        // Cegah super admin menghapus dirinya sendiri
        if ($user->id === auth()->id()) {
            return back()->with('error', 'Anda tidak dapat menghapus akun sendiri.');
        }

        $user->delete();

        return redirect()->route('admin.user.index')
            ->with('success', 'User berhasil dihapus.');
    }
}
