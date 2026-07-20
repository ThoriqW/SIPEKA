<?php

namespace App\Http\Controllers\Admin;

use App\Exports\KebutuhanExport;
use App\Http\Controllers\Controller;
use App\Models\Opd;
use App\Services\FlattenedTreeService;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class KebutuhanController extends Controller
{
    public function __construct(
        private FlattenedTreeService $flattenedTreeService,
    ) {}

    /**
     * Tampilkan tabel pohon Bezetting (sebelumnya Kebutuhan).
     * Tanpa proyeksi tahun — hanya data saat ini.
     */
    public function index(Request $request)
    {
        $opdId = $request->filled('opd_id') ? (int) $request->opd_id : null;
        $tree = $this->flattenedTreeService->buildFlatTree(
            opdId: $opdId,
            includeRoot: true,
            withProjections: false,
        );
        $opdList = Opd::orderBy('nama_opd')->pluck('nama_opd', 'id');

        return view('admin.kebutuhan.index', compact('tree', 'opdList'));
    }

    /**
     * Export Bezetting ke Excel.
     */
    public function export(Request $request)
    {
        $opdId = $request->filled('opd_id') ? (int) $request->opd_id : null;
        $tree = $this->flattenedTreeService->buildFlatTree(
            opdId: $opdId,
            includeRoot: true,
            withProjections: false,
        );

        return Excel::download(
            new KebutuhanExport($tree, []),
            'bezetting-' . date('Y-m-d') . '.xlsx'
        );
    }
}
