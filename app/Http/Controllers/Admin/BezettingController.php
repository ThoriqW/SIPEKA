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
     * Tampilkan tabel pohon Kebutuhan (sebelumnya Bezetting).
     * Dengan proyeksi pensiun & kebutuhan 5 tahun, hanya pegawai pensiun.
     */
    public function index()
    {
        $tree = $this->flattenedTreeService->buildFlatTree(
            opdId: null,
            includeRoot: true,
            withProjections: true,
        );

        $tahunLabels = $this->projectionService->getTahunLabels();

        return view('admin.bezetting.index', compact('tree', 'tahunLabels'));
    }

    /**
     * Export Kebutuhan ke Excel.
     */
    public function export()
    {
        $tree = $this->flattenedTreeService->buildFlatTree(
            opdId: null,
            includeRoot: true,
            withProjections: true,
        );

        $tahunLabels = $this->projectionService->getTahunLabels();

        return Excel::download(
            new BezettingExport($tree, $tahunLabels),
            'kebutuhan-' . date('Y-m-d') . '.xlsx'
        );
    }
}
