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
        $opdId = $request->filled('unor_id') ? (int) $request->unor_id : null;
        $tree = $this->flattenedTreeService->buildFlatTree(
            unorId: $opdId,
            withProjections: false,
        );
        $opdList = Unor::whereNotNull('parent_id')
            ->whereHas('parent', fn($q) => $q->whereNull('parent_id'))
            ->orderBy('nama_unor')->pluck('nama_unor', 'id');

        return view('admin.bezetting.index', compact('tree', 'opdList'));
    }

    /**
     * Export Bezetting ke Excel.
     */
    public function export(Request $request)
    {
        $opdId = $request->filled('unor_id') ? (int) $request->unor_id : null;
        $tree = $this->flattenedTreeService->buildFlatTree(
            unorId: $opdId,
            withProjections: false,
        );

        return Excel::download(
            new KebutuhanExport($tree, []),
            'bezetting-' . date('Y-m-d') . '.xlsx'
        );
    }
}
