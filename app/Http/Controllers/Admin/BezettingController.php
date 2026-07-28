<?php

namespace App\Http\Controllers\Admin;

use App\Exports\KebutuhanExport;
use App\Http\Controllers\Controller;
use App\Models\Unor;
use App\Services\FlattenedTreeService;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class BezettingController extends Controller
{
    public function __construct(
        private FlattenedTreeService $flattenedTreeService,
    ) {}

    /**
     * Tampilkan tabel pohon Bezetting — tanpa proyeksi, data saat ini.
     */
    public function index(Request $request)
    {
        $opdId = $request->filled('opd_id') ? (int) $request->opd_id : null;
        $tree = $this->flattenedTreeService->buildFlatTree(
            unorId: $opdId,
            includeRoot: true,
            withProjections: false,
        );
        $opdList = Unor::orderBy('nama_unor')->pluck('nama_unor', 'id');

        return view('admin.kebutuhan.index', compact('tree', 'opdList'));
    }

    /**
     * Export Bezetting ke Excel.
     */
    public function export(Request $request)
    {
        $opdId = $request->filled('opd_id') ? (int) $request->opd_id : null;
        $tree = $this->flattenedTreeService->buildFlatTree(
            unorId: $opdId,
            includeRoot: true,
            withProjections: false,
        );

        return Excel::download(
            new KebutuhanExport($tree, []),
            'bezetting-' . date('Y-m-d') . '.xlsx'
        );
    }
}
