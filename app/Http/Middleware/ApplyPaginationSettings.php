<?php

namespace App\Http\Middleware;

use App\Models\SiteSetting;
use Closure;
use Illuminate\Http\Request;

class ApplyPaginationSettings
{
    public function handle(Request $request, Closure $next)
    {
        $perPage = 10;

        try {
            $settings = SiteSetting::query()->select('items_per_page')->first();
            if ($settings && is_numeric($settings->items_per_page)) {
                $perPage = max(1, (int) $settings->items_per_page);
            }
        } catch (\Throwable $e) {
            $perPage = 10;
        }

        $request->attributes->set('per_page', $perPage);

        return $next($request);
    }
}
