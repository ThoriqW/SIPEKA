<?php

namespace App\Http\Controllers\Admin;

use App\Exports\BezettingExport;
use App\Http\Controllers\Controller;
use App\Services\FlattenedTreeService;
use App\Services\ProjectionService;
use Maatwebsite\Excel\Facades\Excel;

class BezettingController extends Controller
{
    public function __construct(
        private FlattenedTreeService $flattenedTreeService,
        private ProjectionService $projectionService,
    ) {}

    /**
     * Tampilkan tabel pohon Bezetting seluruh OPD.
     */
    public function index()
    {
        $user = auth()->user();

        if ($user->isBkd()) {
            $tree = $this->flattenedTreeService->buildFlatTree(
                opdId: null,
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

        return view('admin.bezetting.index', compact('tree', 'tahunLabels'));
    }

    /**
     * Export Bezetting ke Excel.
     */
    public function export()
    {
        $user = auth()->user();

        if ($user->isBkd()) {
            $tree = $this->flattenedTreeService->buildFlatTree(
                opdId: null,
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
            new BezettingExport($tree, $tahunLabels),
            'bezetting-' . date('Y-m-d') . '.xlsx'
        );
    }
}
