<?php

namespace App\Http\Controllers;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\App;
use Illuminate\Http\Request;

class AdminDashboardController extends Controller
{
    private const TABLES = [
        'topic' => 'topics',
        'question' => 'questions',
        'answer' => 'answers',
        'article' => 'articles',
        'comment' => 'comments',
        'reply' => 'replies',
        'user_group' => 'userGroups',
        'user' => 'users',
        'report' => 'reports',
    ];

    public function overview(Request $request)
    {
        $counts = [];
        foreach (array_keys(self::TABLES) as $table) {
            $query = DB::table($table);
            if ($table !== 'user') {
                $query->whereNull('delete_time');
            }
            $counts[$table . '_count'] = $query->count();
        }

        $counts['pending_report_count'] = DB::table('report')
            ->whereNull('delete_time')
            ->where('report_handle_state', 0)
            ->count();

        [$start, $end] = $this->resolveRange($request->query('range', 'last_7_days'));
        $recent = [];
        foreach (array_keys(self::TABLES) as $table) {
            $query = DB::table($table)
                ->selectRaw('DATE(create_time) as date, COUNT(*) as count')
                ->whereBetween('create_time', [$start, $end]);
            if ($table !== 'user') {
                $query->whereNull('delete_time');
            }
            $daily = $query
                ->groupByRaw('DATE(create_time)')
                ->pluck('count', 'date');

            // 使用单数模型名与后台图表组件的 item.key 保持一致。
            $recent[$table] = $this->fillDailyCounts($daily, $start, $end);
        }

        return response()->json([
            'counts' => $counts,
            'recent' => $recent,
            'server' => [
                'MDF_VERSION' => config('app.version', 'MDForum'),
                'OS' => PHP_OS_FAMILY . ' · ' . php_uname('s'),
                'PHP_VERSION' => PHP_VERSION,
                'WEB_SERVER' => $_SERVER['SERVER_SOFTWARE'] ?? '—',
                'DB_VERSION' => $this->databaseVersion(),
                'UPLOAD_MAX_FILESIZE' => ini_get('upload_max_filesize') ?: '—',
                'MAX_EXECUTION_TIME' => (string) ini_get('max_execution_time') . 's',
                'DISK_FREE_SPACE' => $this->formatBytes(@disk_free_space(base_path())),
                'DB_SIZE' => $this->databaseSize(),
                'ENVIRONMENT' => App::environment(),
            ],
            'generated_at' => now()->toIso8601String(),
        ]);
    }

    // 单图数据接口：切换时间范围时只查询当前图表对应的一张表，避免重复跑全量 overview。
    public function chart(Request $request)
    {
        $key = $request->query('key');
        if (! array_key_exists($key, self::TABLES)) {
            return response()->json(['error' => 'invalid_key'], 400);
        }

        [$start, $end] = $this->resolveRange($request->query('range', 'last_7_days'));

        $query = DB::table($key)
            ->selectRaw('DATE(create_time) as date, COUNT(*) as count')
            ->whereBetween('create_time', [$start, $end]);
        if ($key !== 'user') {
            $query->whereNull('delete_time');
        }
        $daily = $query->groupByRaw('DATE(create_time)')->pluck('count', 'date');

        return response()->json([
            'recent' => $this->fillDailyCounts($daily, $start, $end),
        ]);
    }

    private function resolveRange(string $range): array
    {
        $today = Carbon::today();

        return match (true) {
            preg_match('/^\d{4}$/', $range) === 1 => [
                Carbon::createFromFormat('Y-m-d', $range . '-01-01')->startOfDay(),
                Carbon::createFromFormat('Y-m-d', $range . '-12-31')->endOfDay(),
            ],
            $range === 'this_month' => [$today->copy()->startOfMonth(), $today->copy()->endOfDay()],
            $range === 'last_month' => [$today->copy()->subMonthNoOverflow()->startOfMonth(), $today->copy()->subMonthNoOverflow()->endOfMonth()],
            $range === 'last_30_days' => [$today->copy()->subDays(29)->startOfDay(), $today->copy()->endOfDay()],
            $range === 'this_year' => [$today->copy()->startOfYear(), $today->copy()->endOfDay()],
            $range === 'last_year' => [$today->copy()->subYear()->startOfYear(), $today->copy()->subYear()->endOfYear()],
            $range === 'last_1_year' => [$today->copy()->subYear()->addDay()->startOfDay(), $today->copy()->endOfDay()],
            default => [$today->copy()->subDays(6)->startOfDay(), $today->copy()->endOfDay()],
        };
    }

    private function fillDailyCounts($daily, Carbon $start, Carbon $end): array
    {
        $result = [];
        for ($date = $start->copy(); $date->lte($end); $date->addDay()) {
            $dateString = $date->toDateString();
            $result[] = [
                'date' => $dateString,
                'count' => (int) ($daily[$dateString] ?? 0),
            ];
        }

        return $result;
    }

    private function databaseVersion(): string
    {
        try {
            return match (DB::getDriverName()) {
                'sqlite' => (string) (DB::selectOne('select sqlite_version() as version')->version ?? '—'),
                'mysql', 'mariadb' => (string) (DB::selectOne('select version() as version')->version ?? '—'),
                'pgsql' => (string) (DB::selectOne('select version()')->version ?? '—'),
                default => '—',
            };
        } catch (\Throwable) {
            return '—';
        }
    }

    private function databaseSize(): string
    {
        try {
            if (DB::getDriverName() === 'sqlite') {
                $path = config('database.connections.sqlite.database');
                return $this->formatBytes(is_string($path) ? @filesize($path) : null);
            }

            if (in_array(DB::getDriverName(), ['mysql', 'mariadb'], true)) {
                $database = DB::getDatabaseName();
                $size = DB::table('information_schema.tables')
                    ->where('table_schema', $database)
                    ->sum(DB::raw('data_length + index_length'));
                return $this->formatBytes($size);
            }
        } catch (\Throwable) {
            // 数据库驱动不支持大小查询时返回占位值。
        }

        return '—';
    }

    private function formatBytes($bytes): string
    {
        if (! is_numeric($bytes) || (float) $bytes <= 0) {
            return '—';
        }

        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $index = min((int) floor(log((float) $bytes, 1024)), count($units) - 1);
        return round((float) $bytes / (1024 ** $index), 2) . ' ' . $units[$index];
    }
}