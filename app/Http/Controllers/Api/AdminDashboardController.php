<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Models\SiteSetting;
use Illuminate\Http\Request;

class AdminDashboardController extends Controller
{
    public function index(Request $request)
    {
        $year = $request->query("year");
        $month = $request->query("month");
        $status = $request->query("status");
        $search = $request->query("search");
        $currencyId = $request->query('currency_id');

        $base = Invoice::query();

        if ($year)  $base->whereYear('date', (int)$year);
        if ($month) $base->whereMonth('date', (int)$month);

        if ($search) {
            $base->where("number", "like", "%{$search}%");
        }
        if ($currencyId) {
            $base->where("currency_id", (int)$currencyId);
        }

        if ($status) {
            if ($status === 'unpaid') {
                $base->where('paid', '<=', 0);
            } elseif ($status === 'paid') {
                $base->whereColumn('paid', '>=', 'total');
            } elseif ($status === 'partial') {
                $base->where('paid', '>', 0)->whereColumn('paid', '<', 'total');
            }
        }

        $invoicesCountAll = (clone $base)->count();

        $totalsByCurrency = (clone $base)->selectRaw("
        currency_id,
        COUNT(*) as invoices_count,
        COALESCE(SUM(total),0) as total_sum,
        COALESCE(SUM(paid),0) as paid_sum,
        COALESCE(SUM(discount),0) as discount_sum,
        COALESCE(SUM(total - paid),0) as due_sum
    ")
            ->groupBy('currency_id')
            ->orderBy('currency_id')
            ->get();

        $chartByCurrency = (clone $base)->selectRaw("
        currency_id,
        DATE(date) as day,
        COUNT(*) as invoices_count,
        COALESCE(SUM(total),0) as total_sum,
        COALESCE(SUM(paid),0) as paid_sum,
        COALESCE(SUM(total - paid),0) as due_sum
    ")
            ->groupBy('currency_id', 'day')
            ->orderBy('currency_id')
            ->orderBy('day')
            ->get();

        return response()->json([
            'filters' => [
                'year' => $year,
                'month' => $month,
                'status' => $status,
                'search' => $search,
                'currency_id' => $currencyId,
            ],
            'summary_all' => [
                'invoices_count' => $invoicesCountAll,
            ],
            'totals_by_currency' => $totalsByCurrency,
            'chart_by_currency' => $chartByCurrency,
        ]);
    }
}
