<?php

namespace App\Http\Controllers\Admin;

use App\Exports\BezettingExport;
use App\Http\Controllers\Controller;
use App\Services\FlattenedTreeService;
use Maatwebsite\Excel\Facades\Excel;

class BezettingController extends Controller
{
    public function __construct(
        private FlattenedTreeService $flattenedTreeService,
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

        return view('admin.bezetting.index', compact('tree'));
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

        return Excel::download(
            new BezettingExport($tree),
            'bezetting-' . date('Y-m-d') . '.xlsx'
        );
    }
}
