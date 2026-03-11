<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\Invoice;
use Illuminate\Http\Request;

class ClientDashboardController extends Controller
{
    public function show(Request $request, Client $client)
    {
        $perPage = (int) $request->attributes->get('per_page', 10);

        $base = Invoice::query()->where('client_id', $client->id);

        // فلترة بالعملة باستخدام currency_id
        if ($currencyId = $request->query('currency_id')) {
            $base->where('currency_id', (int) $currencyId);
        }

        if ($year = $request->query('year')) {
            $base->whereYear('date', (int) $year);
        }

        if ($month = $request->query('month')) {
            $base->whereMonth('date', (int) $month);
        }

        if ($paymentStatus = $request->query('payment_status')) {
            $base->where('payment_status', $paymentStatus);
        }

        if ($status = $request->query('status')) {
            if ($status === 'unpaid') {
                $base->where('paid', '<=', 0);
            } elseif ($status === 'paid') {
                $base->whereColumn('paid', '>=', 'total');
            } elseif ($status === 'partial') {
                $base->where('paid', '>', 0)->whereColumn('paid', '<', 'total');
            }
        }

        if ($s = $request->query('search')) {
            $base->where('number', 'like', "%$s%");
        }

        $summaryRow = (clone $base)->selectRaw("
        COALESCE(SUM(total),0) as total_sum,
        COALESCE(SUM(paid),0) as paid_sum,
        COALESCE(SUM(discount),0) as discount_sum,
        COALESCE(SUM(total - paid),0) as due_sum
    ")->first();

        $totalsByCurrency = (clone $base)->selectRaw("
        currency_id,
        COALESCE(SUM(total),0) as total_sum,
        COALESCE(SUM(paid),0) as paid_sum,
        COALESCE(SUM(discount),0) as discount_sum,
        COALESCE(SUM(total - paid),0) as due_sum
    ")
            ->groupBy('currency_id')
            ->orderBy('currency_id')
            ->get();

        $chart = (clone $base)->selectRaw("
        DATE(date) as day,
        COALESCE(SUM(total),0) as total_sum,
        COALESCE(SUM(paid),0) as paid_sum,
        COALESCE(SUM(total - paid),0) as due_sum
    ")
            ->groupBy('day')
            ->orderBy('day')
            ->get();

        $invoices = (clone $base)
            ->latest('id')
            ->paginate($perPage);

        return response()->json([
            'client' => $client,
            'filters' => [
                'currency_id' => $currencyId,
                'year' => $year,
                'month' => $month,
                'payment_status' => $paymentStatus,
                'status' => $status,
                'search' => $s,
            ],
            'summary' => [
                'currency_id' => $currencyId,
                'total_sum' => (float) $summaryRow->total_sum,
                'paid_sum' => (float) $summaryRow->paid_sum,
                'discount_sum' => (float) $summaryRow->discount_sum,
                'due_sum' => (float) $summaryRow->due_sum,
            ],
            'totals_by_currency' => $totalsByCurrency,
            'chart' => $chart,
            'invoices' => $invoices,
        ]);
    }
}
