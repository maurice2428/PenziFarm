<?php

namespace App\Http\Controllers\Assets;

use App\Http\Controllers\Controller;
use App\Models\FarmAsset;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;

class AssetValuationReportController extends Controller
{
    public function __invoke(Request $request)
    {
        abort_unless(
            auth()->user()?->can('view assets') ||
                auth()->user()?->hasRole('Admin') ||
                auth()->user()?->hasRole('Administrator'),
            403
        );

        $assets = FarmAsset::query()
            ->with(['location', 'supplier', 'latestValuation'])
            ->when($request->query('category'), fn($query, $category) => $query->where('category', $category))
            ->when($request->query('status'), fn($query, $status) => $query->where('status', $status))
            ->orderBy('category')
            ->orderBy('name')
            ->get();

        $pdf = Pdf::loadView('pdfs.assets.asset-valuation-report', [
            'assets' => $assets,
            'generatedBy' => auth()->user(),
        ])
            ->setPaper('a4', 'landscape')
            ->setOption([
                'isHtml5ParserEnabled' => true,
                'isRemoteEnabled' => true,
                'isPhpEnabled' => true,
            ]);

        return $pdf->stream('Asset-Valuation-Report-' . now('Africa/Nairobi')->format('Ymd-His') . '.pdf');
    }
}
