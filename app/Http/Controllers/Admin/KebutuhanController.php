<?php

namespace App\Http\Controllers\Admin;

use App\Exports\BezettingExport;
use App\Http\Controllers\Controller;
use App\Models\Unor;
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
     * Tampilkan tabel pohon Kebutuhan — dengan proyeksi pensiun & kebutuhan 5 tahun.
     *
     * Hanya root dan anak langsungnya yang dikirim; baris lebih dalam dimuat
     * lewat children() saat node-nya dibuka.
     */
    public function index(Request $request)
    {
        $opdId = $request->filled('unor_id') ? (int) $request->unor_id : null;

        $tree = $this->flattenedTreeService->buildInitialRows(
            unorId: $opdId,
            withProjections: true,
        );

        $tahunLabels = $this->projectionService->getTahunLabels();

        return view('admin.kebutuhan.index', [
            'tree' => $tree,
            'tahunLabels' => $tahunLabels,
            'opdList' => Unor::perangkatDaerah()->pluck('nama_unor', 'id'),
            'childrenRoute' => 'admin.kebutuhan.children',
            'childrenRouteParams' => array_filter(['root_unor_id' => $opdId]),
            'colspan' => 16,
        ]);
    }

    /**
     * Baris anak langsung satu UNOR — dipanggil saat node dibuka di klien.
     *
     * root_unor_id adalah Perangkat Daerah yang sedang menjadi filter halaman;
     * dipakai hanya untuk menghitung level/indentasi baris.
     */
    public function children(Request $request, Unor $unor)
    {
        $rootUnorId = $request->filled('root_unor_id') ? (int) $request->root_unor_id : null;

        $rows = $this->flattenedTreeService->buildChildrenRows(
            unorId: $unor->id,
            withProjections: true,
            pageRootUnorId: $rootUnorId,
        );

        return response()->json([
            'html' => view('admin.kebutuhan._rows', [
                'tree' => $rows,
                'childrenRoute' => 'admin.kebutuhan.children',
                'childrenRouteParams' => array_filter(['root_unor_id' => $rootUnorId]),
            ])->render(),
            'count' => count($rows),
        ]);
    }

    /**
     * Export Kebutuhan ke Excel — mengikuti filter yang sedang aktif di layar.
     */
    public function export(Request $request)
    {
        $opdId = $request->filled('unor_id') ? (int) $request->unor_id : null;

        $tree = $this->flattenedTreeService->buildFlatTree(
            unorId: $opdId,
            withProjections: true,
        );

        $tahunLabels = $this->projectionService->getTahunLabels();

        return Excel::download(
            new BezettingExport($tree, $tahunLabels),
            'kebutuhan-' . date('Y-m-d') . '.xlsx'
        );
    }
}
