<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;

class AnalyticsController extends Controller
{
    public function collect(Request $request): Response
    {
        $path = $this->normalizePath((string) $request->query('p', '/'));
        $host = $this->normalizeHost((string) $request->query('h', $request->getHost()));

        if (
            Schema::hasTable('analytics_page_views') &&
            $path !== '/a.gif' &&
            $path !== '/transparency/analytics' &&
            $path !== '/transparency/analytics.json'
        ) {
            $now = Carbon::now();
            $date = $now->toDateString();

            $updated = DB::table('analytics_page_views')
                ->where('event_date', $date)
                ->where('host', $host)
                ->where('path', $path)
                ->update([
                    'views' => DB::raw('views + 1'),
                    'last_seen_at' => $now,
                    'updated_at' => $now,
                ]);

            if ($updated === 0) {
                try {
                    DB::table('analytics_page_views')->insert([
                        'event_date' => $date,
                        'host' => $host,
                        'path' => $path,
                        'views' => 1,
                        'last_seen_at' => $now,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ]);
                } catch (\Throwable) {
                    DB::table('analytics_page_views')
                        ->where('event_date', $date)
                        ->where('host', $host)
                        ->where('path', $path)
                        ->update([
                            'views' => DB::raw('views + 1'),
                            'last_seen_at' => $now,
                            'updated_at' => $now,
                        ]);
                }
            }
        }

        $gif = base64_decode('R0lGODlhAQABAPAAAAAAAAAAACH5BAEAAAAALAAAAAABAAEAAAICRAEAOw==');

        return response($gif, 200, [
            'Content-Type' => 'image/gif',
            'Cache-Control' => 'no-store, no-cache, must-revalidate, max-age=0',
        ]);
    }

    public function dashboard(): View
    {
        $fromDate = Carbon::now()->subDays(29)->toDateString();
        if (! Schema::hasTable('analytics_page_views')) {
            return view('transparency.analytics', [
                'fromDate' => $fromDate,
                'totals' => (object) ['views' => 0, 'rows_count' => 0],
                'topPages' => collect(),
                'daily' => collect(),
                'hosts' => collect(),
            ]);
        }

        $totals = DB::table('analytics_page_views')
            ->selectRaw('SUM(views) as views')
            ->selectRaw('COUNT(*) as rows_count')
            ->where('event_date', '>=', $fromDate)
            ->first();

        $topPages = DB::table('analytics_page_views')
            ->select('host', 'path')
            ->selectRaw('SUM(views) as views')
            ->where('event_date', '>=', $fromDate)
            ->groupBy('host', 'path')
            ->orderByDesc('views')
            ->limit(25)
            ->get();

        $daily = DB::table('analytics_page_views')
            ->select('event_date')
            ->selectRaw('SUM(views) as views')
            ->where('event_date', '>=', $fromDate)
            ->groupBy('event_date')
            ->orderBy('event_date')
            ->get();

        $hosts = DB::table('analytics_page_views')
            ->select('host')
            ->selectRaw('SUM(views) as views')
            ->where('event_date', '>=', $fromDate)
            ->groupBy('host')
            ->orderByDesc('views')
            ->get();

        return view('transparency.analytics', [
            'fromDate' => $fromDate,
            'totals' => $totals,
            'topPages' => $topPages,
            'daily' => $daily,
            'hosts' => $hosts,
        ]);
    }

    public function dashboardJson(): JsonResponse
    {
        $fromDate = Carbon::now()->subDays(29)->toDateString();
        if (! Schema::hasTable('analytics_page_views')) {
            return response()->json([
                'window_days' => 30,
                'from_date' => $fromDate,
                'daily' => [],
                'top_pages' => [],
                'policy' => [
                    'stores' => ['date', 'host', 'path', 'aggregate view count'],
                    'does_not_store' => ['ip address', 'cookies', 'fingerprint id', 'wallet address', 'user account'],
                ],
            ]);
        }

        $daily = DB::table('analytics_page_views')
            ->select('event_date')
            ->selectRaw('SUM(views) as views')
            ->where('event_date', '>=', $fromDate)
            ->groupBy('event_date')
            ->orderBy('event_date')
            ->get();

        $topPages = DB::table('analytics_page_views')
            ->select('host', 'path')
            ->selectRaw('SUM(views) as views')
            ->where('event_date', '>=', $fromDate)
            ->groupBy('host', 'path')
            ->orderByDesc('views')
            ->limit(100)
            ->get();

        return response()->json([
            'window_days' => 30,
            'from_date' => $fromDate,
            'daily' => $daily,
            'top_pages' => $topPages,
            'policy' => [
                'stores' => ['date', 'host', 'path', 'aggregate view count'],
                'does_not_store' => ['ip address', 'cookies', 'fingerprint id', 'wallet address', 'user account'],
            ],
        ]);
    }

    private function normalizePath(string $path): string
    {
        $path = trim($path);
        if ($path === '') {
            return '/';
        }
        if ($path[0] !== '/') {
            $path = '/'.$path;
        }

        $path = preg_replace('/\s+/', '', $path) ?? '/';
        $path = substr($path, 0, 255);

        return $path === '' ? '/' : $path;
    }

    private function normalizeHost(string $host): string
    {
        $host = strtolower(trim($host));
        $host = preg_replace('/[^a-z0-9\.\-]/', '', $host) ?? '';
        $host = substr($host, 0, 120);

        return $host === '' ? 'unknown' : $host;
    }
}
