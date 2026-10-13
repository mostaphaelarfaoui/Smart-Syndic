<?php

namespace App\Services\Server;

use Illuminate\Support\Facades\File;

class LogViewerService
{
    protected string $logPath;

    public function __construct()
    {
        $this->logPath = storage_path('logs/laravel.log');
    }

    public function getLogPath(): string
    {
        return $this->logPath;
    }

    public function getLogFileSize(): string
    {
        if (! File::exists($this->logPath)) {
            return '0 KB';
        }

        $bytes = File::size($this->logPath);
        if ($bytes >= 1048576) {
            return round($bytes / 1048576, 2) . ' MB';
        }

        return round($bytes / 1024, 2) . ' KB';
    }

    public function getLogs(int $limit = 80, ?string $level = null, ?string $search = null): array
    {
        if (! File::exists($this->logPath)) {
            return [];
        }

        $content = File::get($this->logPath);
        if (empty(trim($content))) {
            return [];
        }

        // Split into log chunks by standard Laravel pattern: [YYYY-MM-DD HH:MM:SS]
        $pattern = '/^\[(\d{4}-\d{2}-\d{2}[ T]\d{2}:\d{2}:\d{2}[^\]]*)\]\s+([a-zA-Z0-9_\-]+)\.([a-zA-Z0-9_\-]+):\s+(.*)$/m';

        preg_match_all($pattern, $content, $matches, PREG_OFFSET_CAPTURE);

        $entries = [];
        $totalMatches = count($matches[0]);

        if ($totalMatches === 0) {
            return [];
        }

        for ($i = $totalMatches - 1; $i >= 0; $i--) {
            $timestamp = $matches[1][$i][0];
            $environment = $matches[2][$i][0];
            $entryLevel = strtoupper($matches[3][$i][0]);
            $title = $matches[4][$i][0];

            $startPos = $matches[0][$i][1];
            $endPos = ($i + 1 < $totalMatches) ? $matches[0][$i + 1][1] : strlen($content);
            $fullBody = substr($content, $startPos, $endPos - $startPos);

            // Filter by level
            if ($level && $entryLevel !== strtoupper($level)) {
                continue;
            }

            // Filter by search
            if ($search && ! stripos($fullBody, $search)) {
                continue;
            }

            $entries[] = [
                'timestamp'   => $timestamp,
                'environment' => $environment,
                'level'       => $entryLevel,
                'title'       => $title,
                'body'        => trim($fullBody),
            ];

            if (count($entries) >= $limit) {
                break;
            }
        }

        return $entries;
    }

    public function clear(): bool
    {
        if (File::exists($this->logPath)) {
            File::put($this->logPath, '');
            return true;
        }

        return false;
    }
}
