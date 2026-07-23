<?php

namespace App\Http\Controllers\Admin;

use App\Exports\KebutuhanExport;
use App\Http\Controllers\Controller;
use App\Models\Opd;
use App\Services\NodeTreeBuilder;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class KebutuhanController extends Controller
{
    public function __construct(
        private NodeTreeBuilder $nodeTreeBuilder,
    ) {}

    /**
     * Tampilkan tabel pohon Bezetting.
     * Hanya menampilkan node POSISI.
     */
    public function index(Request $request)
    {
        $unitId = $request->filled('unit_id') ? (int) $request->unit_id : null;

        $tree = $this->nodeTreeBuilder->buildFlatTree(
            unitId: $unitId,
            includeRoot: true,
            withProjections: false,
            onlyPosisi: true,
        );

        $opdList = Opd::orderBy('nama_opd')->pluck('nama_opd', 'id');

        return view('admin.kebutuhan.index', compact('tree', 'opdList'));
    }

    /**
     * Export Bezetting ke Excel.
     */
    public function export(Request $request)
    {
        $unitId = $request->filled('unit_id') ? (int) $request->unit_id : null;
        $tree = $this->nodeTreeBuilder->buildFlatTree(
            unitId: $unitId,
            includeRoot: true,
            withProjections: false,
            onlyPosisi: true,
        );

        return Excel::download(
            new KebutuhanExport($tree, []),
            'bezetting-' . date('Y-m-d') . '.xlsx'
        );
    }
}
