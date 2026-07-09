<?php

namespace App\Http\Controllers\Inventory;

use App\Http\Controllers\Controller;
use App\Models\StockMovement;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;

class StockMovementReportController extends Controller
{
    public function __invoke(Request $request)
    {
        abort_unless(
            auth()->user()?->can('view stock movements')
            || auth()->user()?->hasRole('Admin')
            || auth()->user()?->hasRole('Administrator'),
            403
        );

        $from = $request->query('from');
        $to = $request->query('to');
        $direction = $request->query('direction');
        $type = $request->query('type');

        $movements = StockMovement::query()
            ->with(['inventoryItem', 'purchaseOrder', 'referenceable', 'createdBy'])
            ->when($from, fn ($query) => $query->whereDate('movement_date', '>=', $from))
            ->when($to, fn ($query) => $query->whereDate('movement_date', '<=', $to))
            ->when($direction, fn ($query) => $query->where('direction', $direction))
            ->when($type, fn ($query) => $query->where('type', $type))
            ->orderBy('movement_date')
            ->orderBy('id')
            ->get();

        $pdf = Pdf::loadView('pdfs.inventory.stock-movement-report', [
            'movements' => $movements,
            'from' => $from,
            'to' => $to,
            'direction' => $direction,
            'type' => $type,
            'generatedBy' => auth()->user(),
        ])
            ->setPaper('a4', 'landscape')
            ->setOption([
                'isHtml5ParserEnabled' => true,
                'isRemoteEnabled' => true,
                'isPhpEnabled' => true,
            ]);

        return $pdf->stream('Stock-Movement-Report-' . now('Africa/Nairobi')->format('Ymd-His') . '.pdf');
    }
}
