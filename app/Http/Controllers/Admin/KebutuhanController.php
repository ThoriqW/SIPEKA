<?php

namespace App\Http\Controllers\Admin;

use App\Exports\BezettingExport;
use App\Http\Controllers\Controller;
use App\Models\Unor;
use App\Services\FlattenedTreeService;
use App\Services\ProjectionService;
use Maatwebsite\Excel\Facades\Excel;

class KebutuhanController extends Controller
{
    public function __construct(
        private FlattenedTreeService $flattenedTreeService,
        private ProjectionService $projectionService,
    ) {}

    /**
     * Tampilkan tabel pohon Kebutuhan — dengan proyeksi pensiun & kebutuhan 5 tahun.
     *
     * Hanya root dan anak langsungnya yang dikirim; baris lebih dalam dimuat
     * lewat children() saat node-nya dibuka.
     */
    public function index()
    {
        $tree = $this->flattenedTreeService->buildInitialRows(
            unorId: null,
            withProjections: true,
        );

        $tahunLabels = $this->projectionService->getTahunLabels();

        return view('admin.kebutuhan.index', [
            'tree' => $tree,
            'tahunLabels' => $tahunLabels,
            'childrenRoute' => 'admin.kebutuhan.children',
            'childrenRouteParams' => [],
            'colspan' => 16,
        ]);
    }

    /**
     * Baris anak langsung satu UNOR — dipanggil saat node dibuka di klien.
     */
    public function children(Unor $unor)
    {
        $rows = $this->flattenedTreeService->buildChildrenRows(
            unorId: $unor->id,
            withProjections: true,
        );

        return response()->json([
            'html' => view('admin.kebutuhan._rows', [
                'tree' => $rows,
                'childrenRoute' => 'admin.kebutuhan.children',
                'childrenRouteParams' => [],
            ])->render(),
            'count' => count($rows),
        ]);
    }

    /**
     * Export Kebutuhan ke Excel.
     */
    public function export()
    {
        $tree = $this->flattenedTreeService->buildFlatTree(
            unorId: null,
            withProjections: true,
        );

        $tahunLabels = $this->projectionService->getTahunLabels();

        return Excel::download(
            new BezettingExport($tree, $tahunLabels),
            'kebutuhan-' . date('Y-m-d') . '.xlsx'
        );
    }
}
