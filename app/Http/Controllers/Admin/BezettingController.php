<?php

namespace App\Http\Controllers\Admin;

use App\Exports\BezettingExport;
use App\Http\Controllers\Controller;
use App\Services\NodeTreeBuilder;
use App\Services\ProjectionService;
use Maatwebsite\Excel\Facades\Excel;

class BezettingController extends Controller
{
    public function __construct(
        private NodeTreeBuilder $nodeTreeBuilder,
        private ProjectionService $projectionService,
    ) {}

    /**
     * Tampilkan tabel pohon Kebutuhan.
     * Menampilkan seluruh pohon (UNIT + POSISI) dengan proyeksi 5 tahun.
     */
    public function index()
    {
        $tree = $this->nodeTreeBuilder->buildFlatTree(
            unitId: null,
            includeRoot: true,
            withProjections: true,
            onlyPosisi: false,
        );

        $tahunLabels = $this->projectionService->getTahunLabels();

        return view('admin.bezetting.index', compact('tree', 'tahunLabels'));
    }

    /**
     * Export Kebutuhan ke Excel.
     */
    public function export()
    {
        $tree = $this->nodeTreeBuilder->buildFlatTree(
            unitId: null,
            includeRoot: true,
            withProjections: true,
            onlyPosisi: false,
        );

        $tahunLabels = $this->projectionService->getTahunLabels();

        return Excel::download(
            new BezettingExport($tree, $tahunLabels),
            'kebutuhan-' . date('Y-m-d') . '.xlsx'
        );
    }
}
