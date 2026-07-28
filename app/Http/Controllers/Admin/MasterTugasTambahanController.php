<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\MasterTugasTambahan;
use Illuminate\Http\Request;

class MasterTugasTambahanController extends Controller
{
    public function index()
    {
        $list = MasterTugasTambahan::orderBy('nama_tugas')->paginate(15);
        return view('admin.tugas-tambahan.index', compact('list'));
    }

    public function create()
    {
        return view('admin.tugas-tambahan.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'nama_tugas' => 'required|string|max:255|unique:master_tugas_tambahan,nama_tugas',
        ]);
        MasterTugasTambahan::create($validated);
        return redirect()->route('admin.tugas-tambahan.index')->with('success', 'Tugas Tambahan berhasil ditambahkan.');
    }

    public function edit(MasterTugasTambahan $tugasTambahan)
    {
        return view('admin.tugas-tambahan.edit', compact('tugasTambahan'));
    }

    public function update(Request $request, MasterTugasTambahan $tugasTambahan)
    {
        $validated = $request->validate([
            'nama_tugas' => 'required|string|max:255|unique:master_tugas_tambahan,nama_tugas,' . $tugasTambahan->id,
        ]);
        $tugasTambahan->update($validated);
        return redirect()->route('admin.tugas-tambahan.index')->with('success', 'Tugas Tambahan berhasil diperbarui.');
    }

    public function destroy(MasterTugasTambahan $tugasTambahan)
    {
        if ($tugasTambahan->tugasTambahanPegawai()->exists()) {
            return back()->with('error', 'Tugas Tambahan tidak dapat dihapus karena masih digunakan oleh pegawai.');
        }
        $tugasTambahan->delete();
        return redirect()->route('admin.tugas-tambahan.index')->with('success', 'Tugas Tambahan berhasil dihapus.');
    }
}
