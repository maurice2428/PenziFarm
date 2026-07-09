<?php

namespace App\Http\Controllers\Reports;

use App\Http\Controllers\Controller;
use App\Services\Reports\FarmActivityExplorerService;
use Illuminate\Http\Request;

class FarmActivityPrintController extends Controller
{
    public function __invoke(Request $request)
    {
        $data = app(FarmActivityExplorerService::class)->build(
            $request->query('from'),
            $request->query('to')
        );

        return view('reports.farm-activity-explorer-print', [
            'data' => $data,
            'generatedBy' => $request->user(),
        ]);
    }
}
