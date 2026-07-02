<?php

namespace App\Http\Controllers\Admin;

use App\Exports\KebutuhanExport;
use App\Http\Controllers\Controller;
use App\Models\Opd;
use App\Services\FlattenedTreeService;
use App\Services\ProjectionService;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class KebutuhanController extends Controller
{
    public function __construct(
        private FlattenedTreeService $flattenedTreeService,
        private ProjectionService $projectionService,
    ) {}

    /**
     * Tampilkan tabel pohon Kebutuhan.
     */
    public function index(Request $request)
    {
        $user = auth()->user();

        if ($user->isBkd()) {
            $opdId = $request->filled('opd_id') ? (int) $request->opd_id : null;
            $tree = $this->flattenedTreeService->buildFlatTree(
                opdId: $opdId,
                includeRoot: true,
                withProjections: true,
            );
            $opdList = Opd::orderBy('nama_opd')->pluck('nama_opd', 'id');
        } else {
            $opdId = $user->opd_id;
            $tree = $this->flattenedTreeService->buildFlatTree(
                opdId: $opdId,
                includeRoot: false,
                withProjections: true,
            );
            $opdList = collect();
        }

        $tahunLabels = $this->projectionService->getTahunLabels();

        return view('admin.kebutuhan.index', compact('tree', 'opdList', 'tahunLabels'));
    }

    /**
     * Export Kebutuhan ke Excel.
     */
    public function export(Request $request)
    {
        $user = auth()->user();

        if ($user->isBkd()) {
            $opdId = $request->filled('opd_id') ? (int) $request->opd_id : null;
            $tree = $this->flattenedTreeService->buildFlatTree(
                opdId: $opdId,
                includeRoot: true,
                withProjections: true,
            );
        } else {
            $tree = $this->flattenedTreeService->buildFlatTree(
                opdId: $user->opd_id,
                includeRoot: false,
                withProjections: true,
            );
        }

        $tahunLabels = $this->projectionService->getTahunLabels();

        return Excel::download(
            new KebutuhanExport($tree, $tahunLabels),
            'kebutuhan-' . date('Y-m-d') . '.xlsx'
        );
    }
}
