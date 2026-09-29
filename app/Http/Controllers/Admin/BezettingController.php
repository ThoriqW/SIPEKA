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
     *
     * Hanya root dan anak langsungnya yang dikirim; baris lebih dalam dimuat
     * lewat children() saat node-nya dibuka.
     */
    public function index(Request $request)
    {
        $opdId = $request->filled('unor_id') ? (int) $request->unor_id : null;
        $tree = $this->flattenedTreeService->buildInitialRows(
            unorId: $opdId,
            withProjections: false,
        );
        $opdList = Unor::perangkatDaerah()->pluck('nama_unor', 'id');

        return view('admin.bezetting.index', [
            'tree' => $tree,
            'opdList' => $opdList,
            'childrenRoute' => 'admin.bezetting.children',
            'childrenRouteParams' => array_filter(['root_unor_id' => $opdId]),
            'colspan' => 7,
        ]);
    }

    /**
     * Baris anak langsung satu UNOR — dipanggil saat node dibuka di klien.
     *
     * root_unor_id adalah OPD yang sedang menjadi filter halaman; dipakai
     * hanya untuk menghitung level/indentasi baris.
     */
    public function children(Request $request, Unor $unor)
    {
        $rootUnorId = $request->filled('root_unor_id') ? (int) $request->root_unor_id : null;

        $rows = $this->flattenedTreeService->buildChildrenRows(
            unorId: $unor->id,
            withProjections: false,
            pageRootUnorId: $rootUnorId,
        );

        return response()->json([
            'html' => view('admin.bezetting._rows', [
                'tree' => $rows,
                'childrenRoute' => 'admin.bezetting.children',
                'childrenRouteParams' => array_filter(['root_unor_id' => $rootUnorId]),
            ])->render(),
            'count' => count($rows),
        ]);
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
