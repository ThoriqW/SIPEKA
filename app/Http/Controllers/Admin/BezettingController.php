<?php

namespace App\Http\Controllers\Admin;

use App\Exports\BezettingExport;
use App\Http\Controllers\Controller;
use App\Services\FlattenedTreeService;
use App\Services\NodeTreeBuilder;
use App\Services\ProjectionService;
use Maatwebsite\Excel\Facades\Excel;

class BezettingController extends Controller
{
    public function __construct(
        private FlattenedTreeService $flattenedTreeService,
        private NodeTreeBuilder $nodeTreeBuilder,
        private ProjectionService $projectionService,
    ) {}

    /**
     * Tampilkan tabel pohon Kebutuhan.
     * Dengan proyeksi pensiun & kebutuhan 5 tahun.
     *
     * Menggunakan NodeTreeBuilder (model baru) — menampilkan seluruh pohon
     * (UNIT + POSISI) dengan proyeksi.
     */
    public function index()
    {
        $useNewModel = \App\Models\NodeOrganisasi::exists();

        if ($useNewModel) {
            $tree = $this->nodeTreeBuilder->buildFlatTree(
                unitId: null,
                includeRoot: true,
                withProjections: true,
                onlyPosisi: false,  // tampilkan semua node
            );
        } else {
            $tree = $this->flattenedTreeService->buildFlatTree(
                opdId: null,
                includeRoot: true,
                withProjections: true,
            );
        }

        $tahunLabels = $this->projectionService->getTahunLabels();

        return view('admin.bezetting.index', compact('tree', 'tahunLabels'));
    }

    /**
     * Export Kebutuhan ke Excel.
     */
    public function export()
    {
        $useNewModel = \App\Models\NodeOrganisasi::exists();

        if ($useNewModel) {
            $tree = $this->nodeTreeBuilder->buildFlatTree(
                unitId: null,
                includeRoot: true,
                withProjections: true,
                onlyPosisi: false,
            );
        } else {
            $tree = $this->flattenedTreeService->buildFlatTree(
                opdId: null,
                includeRoot: true,
                withProjections: true,
            );
        }

        $tahunLabels = $this->projectionService->getTahunLabels();

        return Excel::download(
            new BezettingExport($tree, $tahunLabels),
            'kebutuhan-' . date('Y-m-d') . '.xlsx'
        );
    }
}
