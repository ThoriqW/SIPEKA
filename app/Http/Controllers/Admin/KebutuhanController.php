<?php

namespace App\Http\Controllers\Admin;

use App\Exports\KebutuhanExport;
use App\Http\Controllers\Controller;
use App\Models\Opd;
use App\Services\FlattenedTreeService;
use App\Services\NodeTreeBuilder;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class KebutuhanController extends Controller
{
    public function __construct(
        private FlattenedTreeService $flattenedTreeService,
        private NodeTreeBuilder $nodeTreeBuilder,
    ) {}

    /**
     * Tampilkan tabel pohon Bezetting.
     * Tanpa proyeksi tahun — hanya data saat ini.
     *
     * Menggunakan NodeTreeBuilder (model baru) dengan flag onlyPosisi=true
     * untuk menampilkan hanya node POSISI.
     */
    public function index(Request $request)
    {
        $unitId = $request->filled('unit_id') ? (int) $request->unit_id : null;
        $opdId = $request->filled('opd_id') ? (int) $request->opd_id : null;

        // Coba gunakan model baru jika ada data di node_organisasi
        $useNewModel = \App\Models\NodeOrganisasi::exists();

        if ($useNewModel) {
            $tree = $this->nodeTreeBuilder->buildFlatTree(
                unitId: $unitId,
                includeRoot: true,
                withProjections: false,
                onlyPosisi: true,
            );
        } else {
            // Fallback ke model lama
            $tree = $this->flattenedTreeService->buildFlatTree(
                opdId: $opdId,
                includeRoot: true,
                withProjections: false,
            );
        }

        $opdList = Opd::orderBy('nama_opd')->pluck('nama_opd', 'id');

        return view('admin.kebutuhan.index', compact('tree', 'opdList'));
    }

    /**
     * Export Bezetting ke Excel.
     */
    public function export(Request $request)
    {
        $useNewModel = \App\Models\NodeOrganisasi::exists();

        if ($useNewModel) {
            $unitId = $request->filled('unit_id') ? (int) $request->unit_id : null;
            $tree = $this->nodeTreeBuilder->buildFlatTree(
                unitId: $unitId,
                includeRoot: true,
                withProjections: false,
                onlyPosisi: true,
            );
        } else {
            $opdId = $request->filled('opd_id') ? (int) $request->opd_id : null;
            $tree = $this->flattenedTreeService->buildFlatTree(
                opdId: $opdId,
                includeRoot: true,
                withProjections: false,
            );
        }

        return Excel::download(
            new KebutuhanExport($tree, []),
            'bezetting-' . date('Y-m-d') . '.xlsx'
        );
    }
}
