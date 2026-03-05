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

        $currency = $request->query('currency');
        if ($currency) {
            $base->where('currency', strtoupper((string)$currency));
        }

        $year = $request->query('year');
        if ($year) {
            $base->whereYear('date', (int) $year);
        }

        $month = $request->query('month');
        if ($month) {
            $base->whereMonth('date', (int) $month);
        }

        // new payment_status filter
        $paymentStatus = $request->query('payment_status');
        if ($paymentStatus) {
            $base->where('payment_status', $paymentStatus);
        }

        // backward compatible status
        $status = $request->query('status');
        if ($status) {
            if ($status === 'unpaid') {
                $base->where('paid', '<=', 0);
            } elseif ($status === 'paid') {
                $base->whereColumn('paid', '>=', 'total');
            } elseif ($status === 'partial') {
                $base->where('paid', '>', 0)->whereColumn('paid', '<', 'total');
            }
        }

        $s = $request->query('search');
        if ($s) {
            $base->where('number', 'like', "%$s%");
        }

        $summaryRow = (clone $base)->selectRaw("
            COALESCE(SUM(total),0) as total_sum,
            COALESCE(SUM(paid),0) as paid_sum,
            COALESCE(SUM(discount),0) as discount_sum,
            COALESCE(SUM(total - paid),0) as due_sum
        ")->first();

        $totalsByCurrency = (clone $base)->selectRaw("
            currency,
            COALESCE(SUM(total),0) as total_sum,
            COALESCE(SUM(paid),0) as paid_sum,
            COALESCE(SUM(discount),0) as discount_sum,
            COALESCE(SUM(total - paid),0) as due_sum
        ")
            ->groupBy('currency')
            ->orderBy('currency')
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
                'currency' => $currency,
                'year' => $year,
                'month' => $month,
                'payment_status' => $paymentStatus,
                'status' => $status,
                'search' => $s,
            ],
            'summary' => [
                'currency' => $currency,
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
