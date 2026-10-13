<?php

namespace App\Services\Server;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

class ServerHealthService
{
    public function getSystemHealth(): array
    {
        // 1. PHP & Environment Info
        $phpVersion = PHP_VERSION;
        $laravelVersion = app()->version();
        $os = php_uname('s') . ' ' . php_uname('r');
        $memoryLimit = ini_get('memory_limit');
        $uploadMax = ini_get('upload_max_filesize');
        $postMax = ini_get('post_max_size');
        $maxExecutionTime = ini_get('max_execution_time') . 's';

        // Memory Usage
        $memoryUsage = round(memory_get_usage(true) / 1024 / 1024, 2); // MB
        $memoryPeak = round(memory_get_peak_usage(true) / 1024 / 1024, 2); // MB

        // 2. Disk Storage
        $rootPath = base_path();
        $totalDisk = @disk_total_space($rootPath) ?: (100 * 1024 * 1024 * 1024);
        $freeDisk  = @disk_free_space($rootPath) ?: (50 * 1024 * 1024 * 1024);
        $usedDisk  = $totalDisk - $freeDisk;
        $diskPercentage = round(($usedDisk / $totalDisk) * 100, 1);

        // 3. Database Info & Latency
        $dbPath = database_path('database.sqlite');
        $dbSize = File::exists($dbPath) ? round(File::size($dbPath) / 1024 / 1024, 2) : 0; // MB

        $start = microtime(true);
        try {
            DB::select('SELECT 1');
            $dbLatency = round((microtime(true) - $start) * 1000, 2); // ms
            $dbStatus = 'ONLINE';
        } catch (\Throwable $e) {
            $dbLatency = 0;
            $dbStatus = 'OFFLINE';
        }

        // Table counts
        $tableCounts = [
            'tenants'            => DB::table('tenants')->count(),
            'users'              => DB::table('users')->count(),
            'apartments'         => DB::table('apartments')->count(),
            'payments'           => DB::table('payments')->count(),
            'expenses'           => DB::table('expenses')->count(),
            'audit_logs'         => DB::table('audit_logs')->count(),
            'security_incidents' => DB::table('security_incidents')->count(),
            'blocked_ips'        => DB::table('blocked_ips')->count(),
        ];

        // SQLite PRAGMA check
        $integrity = 'OK';
        try {
            $check = DB::select('PRAGMA integrity_check');
            $integrity = $check[0]->integrity_check ?? 'OK';
        } catch (\Throwable $e) {
            $integrity = 'ERROR: ' . $e->getMessage();
        }

        // 4. Failed Jobs
        $failedJobs = 0;
        try {
            if (\Illuminate\Support\Facades\Schema::hasTable('failed_jobs')) {
                $failedJobs = DB::table('failed_jobs')->count();
            }
        } catch (\Throwable $e) {
            $failedJobs = 0;
        }

        // 5. Extensions Check
        $requiredExtensions = [
            'pdo_sqlite' => extension_loaded('pdo_sqlite'),
            'openssl'    => extension_loaded('openssl'),
            'mbstring'   => extension_loaded('mbstring'),
            'curl'       => extension_loaded('curl'),
            'gd'         => extension_loaded('gd'),
            'fileinfo'   => extension_loaded('fileinfo'),
            'tokenizer'  => extension_loaded('tokenizer'),
        ];

        return [
            'php_version'        => $phpVersion,
            'laravel_version'    => $laravelVersion,
            'os'                 => $os,
            'memory_limit'       => $memoryLimit,
            'memory_usage_mb'    => $memoryUsage,
            'memory_peak_mb'     => $memoryPeak,
            'upload_max'         => $uploadMax,
            'post_max'           => $postMax,
            'max_execution_time' => $maxExecutionTime,
            'disk_total_gb'      => round($totalDisk / 1024 / 1024 / 1024, 2),
            'disk_free_gb'       => round($freeDisk / 1024 / 1024 / 1024, 2),
            'disk_used_gb'       => round($usedDisk / 1024 / 1024 / 1024, 2),
            'disk_percentage'    => $diskPercentage,
            'db_size_mb'         => $dbSize,
            'db_latency_ms'      => $dbLatency,
            'db_status'          => $dbStatus,
            'db_integrity'       => $integrity,
            'table_counts'       => $tableCounts,
            'failed_jobs'        => $failedJobs,
            'extensions'         => $requiredExtensions,
            'cache_driver'       => config('cache.default'),
            'session_driver'     => config('session.driver'),
            'queue_driver'       => config('queue.default'),
        ];
    }

    /**
     * Reclaim SQLite space and defragment database
     */
    public function vacuumDatabase(): array
    {
        $dbPath = database_path('database.sqlite');
        $beforeSize = File::exists($dbPath) ? File::size($dbPath) : 0;

        $start = microtime(true);
        DB::statement('VACUUM');
        $duration = round((microtime(true) - $start) * 1000, 2);

        $afterSize = File::exists($dbPath) ? File::size($dbPath) : 0;
        $reclaimed = max(0, $beforeSize - $afterSize);

        return [
            'duration_ms'   => $duration,
            'before_bytes'  => $beforeSize,
            'after_bytes'   => $afterSize,
            'reclaimed_kb'  => round($reclaimed / 1024, 2),
        ];
    }
}
