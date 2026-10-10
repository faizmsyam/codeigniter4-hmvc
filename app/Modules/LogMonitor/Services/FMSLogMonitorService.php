<?php

namespace App\Modules\LogMonitor\Services;

use App\Core\FMSController;
use CodeIgniter\Files\File;

/**
 * Reads and indexes log files from WRITEPATH . 'logs/'.
 * All paths are validated against the log directory to prevent traversal.
 */
final class FMSLogMonitorService
{
    private string $logDir;
    private int $tailLines;
    private int $maxFileSize;

    public function __construct(
        ?string $logDir = null,
        int $tailLines = 200,
        int $maxFileSize = 10485760
    ) {
        $this->logDir = $logDir ?? WRITEPATH . 'logs' . DIRECTORY_SEPARATOR;
        $this->tailLines = $tailLines;
        $this->maxFileSize = $maxFileSize;
    }

    /**
     * Returns an array of log file descriptors (name, size, modified).
     *
     * @return list<array{name: string, size: int, modified: int, modified_date: string}>
     */
    public function listFiles(string $relativePath = ''): array
    {
        $this->ensureDirectoryExists();

        $directory = $this->resolveDirectory($relativePath);
        $files = glob($directory . DIRECTORY_SEPARATOR . '*');
        if ($files === false) {
            return [];
        }

        $result = [];
        foreach ($files as $filePath) {
            if (! is_file($filePath)) {
                continue;
            }

            // Reject anything that resolves outside the log directory (path traversal guard)
            $realPath = realpath($filePath);
            if ($realPath === false || ! $this->isInsideLogDirectory($realPath)) {
                continue;
            }

            $stat = stat($filePath);
            if ($stat === false) {
                continue;
            }

            $name = basename($filePath);
            // Skip non-log files (e.g. .gitkeep, index.html)
            if (! str_ends_with(strtolower($name), '.log') && ! str_ends_with(strtolower($name), '.txt')) {
                continue;
            }

            $displayName = $relativePath === '' ? $name : trim($relativePath, '/') . '/' . $name;
            $result[] = [
                'name'         => $displayName,
                'size'         => $stat['size'],
                'modified'     => $stat['mtime'],
                'modified_date' => date('Y-m-d H:i:s', $stat['mtime']),
            ];
        }

        // Sort by modified date descending
        usort($result, static fn (array $a, array $b): int => $b['modified'] <=> $a['modified']);

        return $result;
    }

    /**
     * Returns the tail of a log file, protected against traversal.
     *
     * @return array{filename: string, total_lines: int, tail_lines: int, content: string}
     * @throws \InvalidArgumentException if the file is outside the log directory
     */
    public function readTail(string $filename, ?int $lines = null): array
    {
        $safeName = $this->sanitizeFilename($filename);
        $filePath = rtrim($this->logDir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $safeName;

        // Defensive: resolve realpath and confirm it lives inside logDir
        $realPath = realpath($filePath);
        if ($realPath === false || ! $this->isInsideLogDirectory($realPath)) {
            throw new \InvalidArgumentException('File not found or access denied.');
        }

        clearstatcache(true, $realPath);
        $stat = stat($realPath);
        if ($stat === false) {
            throw new \RuntimeException('Cannot stat file.');
        }

        if ($stat['size'] > $this->maxFileSize) {
            throw new \RuntimeException('File terlalu besar (' . $this->formatBytes($stat['size']) . '). Maksimum: ' . $this->formatBytes($this->maxFileSize) . '.');
        }

        $tail = $lines ?? $this->tailLines;
        $content = $this->tailFile($realPath, $tail);
        $allLines = $this->countLines($realPath);

        // XSS guard: escape HTML entities in log content
        $content = $this->escapeLogContent($content);

        return [
            'filename'    => $safeName,
            'total_lines' => $allLines,
            'tail_lines'  => substr_count($content, "\n") + 1,
            'content'     => $content,
        ];
    }

    /**
     * Returns a line range from a log file.
     *
     * @return array{filename: string, from_line: int, to_line: int, total_lines: int, content: string}
     */
    public function readRange(string $filename, int $fromLine, int $toLine): array
    {
        $safeName = $this->sanitizeFilename($filename);
        $filePath = rtrim($this->logDir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $safeName;

        $realPath = realpath($filePath);
        if ($realPath === false || ! $this->isInsideLogDirectory($realPath)) {
            throw new \InvalidArgumentException('File not found or access denied.');
        }

        clearstatcache(true, $realPath);
        $content = $this->rangeFile($realPath, $fromLine, $toLine);
        $allLines = $this->countLines($realPath);

        $content = $this->escapeLogContent($content);

        return [
            'filename'   => $safeName,
            'from_line'  => $fromLine,
            'to_line'    => $toLine,
            'total_lines' => $allLines,
            'content'    => $content,
        ];
    }

    /**
     * Search pattern inside a log file (basic grep).
     *
     * @return array{filename: string, pattern: string, matches: int, lines: list<string>}
     */
    public function searchFile(string $filename, string $pattern, int $maxResults = 500): array
    {
        $safeName = $this->sanitizeFilename($filename);
        $filePath = rtrim($this->logDir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $safeName;

        $realPath = realpath($filePath);
        if ($realPath === false || ! $this->isInsideLogDirectory($realPath)) {
            throw new \InvalidArgumentException('File not found or access denied.');
        }

        $handle = fopen($realPath, 'r');
        if ($handle === false) {
            throw new \RuntimeException('Cannot open file.');
        }

        $matches = [];
        $lineNum = 0;
        $count = 0;
        $patternEscaped = '/' . preg_quote($pattern, '/') . '/i';

        while (($line = fgets($handle)) !== false && $count < $maxResults) {
            $lineNum++;
            if (preg_match($patternEscaped, $line)) {
                $matches[] = $this->escapeLogContent(rtrim($line));
                $count++;
            }
        }

        fclose($handle);

        return [
            'filename' => $safeName,
            'pattern'  => $pattern,
            'matches'  => $count,
            'lines'    => $matches,
        ];
    }

    /**
     * Download a log file as an attachment.
     *
     * @return array{filename: string, content: string, size: int}
     */
    public function downloadFile(string $filename): array
    {
        $safeName = $this->sanitizeFilename($filename);
        $filePath = rtrim($this->logDir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $safeName;

        $realPath = realpath($filePath);
        if ($realPath === false || ! $this->isInsideLogDirectory($realPath)) {
            throw new \InvalidArgumentException('File not found or access denied.');
        }

        $content = file_get_contents($realPath);
        if ($content === false) {
            throw new \RuntimeException('Cannot read file.');
        }

        return [
            'filename' => $safeName,
            'content'  => $content,
            'size'     => strlen($content),
        ];
    }

    /**
     * Get the resolved base path (real path of the log directory).
     */
    public function getBasePath(): string
    {
        return realpath($this->logDir) ?: $this->logDir;
    }

    /**
     * Resolve and validate a relative path, returning the real path.
     *
     * @throws \InvalidArgumentException if path resolves outside logDir
     */
    public function resolvePath(string $relativePath): string
    {
        $safeName = $this->sanitizeFilename($relativePath);
        $filePath = rtrim($this->logDir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $safeName;
        $realPath = realpath($filePath);
        if ($realPath === false || ! $this->isInsideLogDirectory($realPath)) {
            throw new \InvalidArgumentException('File not found or access denied.');
        }
        return $realPath;
    }

    /**
     * Read log lines with pagination.
     *
     * @return array{lines: list<string>, total_lines: int, has_more: bool}
     */
    public function readLines(string $filename, int $offset = 0, int $limit = 500, string $search = ''): array
    {
        if ($search !== '') {
            return $this->searchLines($filename, $search, $offset, $limit);
        }

        $safeName = $this->sanitizeFilename($filename);
        $filePath = rtrim($this->logDir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $safeName;
        $realPath = realpath($filePath);
        if ($realPath === false || ! $this->isInsideLogDirectory($realPath)) {
            throw new \InvalidArgumentException('File not found or access denied.');
        }

        clearstatcache(true, $realPath);
        $stat = stat($realPath);
        if ($stat !== false && $stat['size'] > $this->maxFileSize) {
            throw new \RuntimeException('File terlalu besar. Maksimum: ' . $this->formatBytes($this->maxFileSize) . '.');
        }

        $totalLines = $this->countLines($realPath);
        $lines = $this->rangeFile($realPath, $offset + 1, $offset + $limit);
        $lines = $this->escapeLogContent($lines);
        $lineArray = explode("\n", rtrim($lines));

        return [
            'lines' => $lineArray,
            'total_lines' => $totalLines,
            'has_more' => ($offset + count($lineArray)) < $totalLines,
        ];
    }

    /**
     * Quick stats for a log file.
     *
     * @return array{filename: string, size: int, size_human: string, modified: int, modified_date: string, total_lines: int}
     */
    public function stats(string $filename): array
    {
        $safeName = $this->sanitizeFilename($filename);
        $filePath = rtrim($this->logDir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $safeName;
        $realPath = realpath($filePath);
        if ($realPath === false || ! $this->isInsideLogDirectory($realPath)) {
            throw new \InvalidArgumentException('File not found or access denied.');
        }

        clearstatcache(true, $realPath);
        $stat = stat($realPath);
        if ($stat === false) {
            throw new \RuntimeException('Cannot stat file.');
        }

        return [
            'filename' => $safeName,
            'size' => $stat['size'],
            'size_human' => $this->formatBytes($stat['size']),
            'modified' => $stat['mtime'],
            'modified_date' => date('Y-m-d H:i:s', $stat['mtime']),
            'total_lines' => $this->countLines($realPath),
        ];
    }

    /**
     * Get a real-time preview of the last N lines (non-caching).
     */
    public function preview(string $filename, int $lines = 10): string
    {
        $safeName = $this->sanitizeFilename($filename);
        $filePath = rtrim($this->logDir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $safeName;

        $realPath = realpath($filePath);
        if ($realPath === false || ! $this->isInsideLogDirectory($realPath)) {
            return '';
        }

        return $this->tailFile($realPath, $lines);
    }

    // ─── Private helpers ────────────────────────────────────────────────────

    private function searchLines(string $filename, string $pattern, int $offset, int $limit): array
    {
        $safeName = $this->sanitizeFilename($filename);
        $filePath = rtrim($this->logDir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $safeName;
        $realPath = realpath($filePath);
        if ($realPath === false || ! $this->isInsideLogDirectory($realPath)) {
            throw new \InvalidArgumentException('File not found or access denied.');
        }

        $fp = fopen($realPath, 'r');
        if ($fp === false) {
            return ['lines' => [], 'total_lines' => 0, 'has_more' => false];
        }

        $matchedLines = [];
        $patternEscaped = '/' . preg_quote($pattern, '/') . '/i';

        while (($line = fgets($fp)) !== false) {
            if (preg_match($patternEscaped, $line)) {
                $matchedLines[] = rtrim($line);
            }
        }

        fclose($fp);

        $totalLines = count($matchedLines);
        $page = array_slice($matchedLines, $offset, $limit);
        $escapedPage = array_map(function (string $l): string {
            return $this->escapeLogContent($l);
        }, $page);

        return [
            'lines' => $escapedPage,
            'total_lines' => $totalLines,
            'has_more' => ($offset + count($page)) < $totalLines,
        ];
    }

    private function ensureDirectoryExists(): void
    {
        if (! is_dir($this->logDir)) {
            if (! mkdir($this->logDir, 0755, true) && ! is_dir($this->logDir)) {
                throw new \RuntimeException('Cannot create log directory: ' . $this->logDir);
            }
        }
    }

    private function sanitizeFilename(string $filename): string
    {
        // Block null bytes and normalize separators.
        $filename = str_replace("\0", '', trim($filename));
        $filename = str_replace('\\', '/', $filename);
        $segments = array_values(array_filter(explode('/', $filename), static fn (string $part): bool => $part !== ''));
        foreach ($segments as $segment) {
            if ($segment === '.' || $segment === '..') {
                throw new \InvalidArgumentException('Path tidak valid.');
            }
        }

        return implode(DIRECTORY_SEPARATOR, $segments);
    }

    private function resolveDirectory(string $relativePath): string
    {
        $base = realpath($this->logDir);
        if ($base === false) {
            throw new \RuntimeException('Folder log tidak tersedia.');
        }

        $safePath = $this->sanitizeFilename($relativePath);
        $directory = $safePath === '' ? $base : realpath($base . DIRECTORY_SEPARATOR . $safePath);
        if ($directory === false || ! is_dir($directory)) {
            throw new \InvalidArgumentException('Folder log tidak ditemukan.');
        }

        if (! $this->isInsideLogDirectory($directory)) {
            throw new \InvalidArgumentException('Akses path ditolak.');
        }

        return $directory;
    }

    private function isInsideLogDirectory(string $realPath): bool
    {
        $base = realpath($this->logDir);
        if ($base === false) {
            return false;
        }
        $prefix = rtrim($base, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
        $checkPath = rtrim($realPath, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
        return str_starts_with($checkPath, $prefix);
    }

    private function tailFile(string $realPath, int $lines): string
    {
        $fp = fopen($realPath, 'r');
        if ($fp === false) {
            return '';
        }

        // Seek to end, then walk backwards
        fseek($fp, 0, SEEK_END);
        $pos = ftell($fp);
        $lineCount = 0;
        $startPos = $pos;

        while ($pos > 0 && $lineCount <= $lines) {
            fseek($fp, --$pos);
            $char = fgetc($fp);
            if ($char === "\n") {
                $lineCount++;
                if ($lineCount > $lines) {
                    $startPos = $pos + 1;
                    break;
                }
            }
        }

        fseek($fp, $startPos);
        $content = stream_get_contents($fp);
        fclose($fp);

        return $content !== false ? $content : '';
    }

    private function rangeFile(string $realPath, int $fromLine, int $toLine): string
    {
        $fp = fopen($realPath, 'r');
        if ($fp === false) {
            return '';
        }

        $result = '';
        $lineNum = 0;
        while (($line = fgets($fp)) !== false) {
            $lineNum++;
            if ($lineNum >= $fromLine && $lineNum <= $toLine) {
                $result .= $line;
            }
            if ($lineNum > $toLine) {
                break;
            }
        }

        fclose($fp);
        return $result;
    }

    private function countLines(string $realPath): int
    {
        $fp = fopen($realPath, 'r');
        if ($fp === false) {
            return 0;
        }

        $count = 0;
        while (fgets($fp) !== false) {
            $count++;
        }

        fclose($fp);
        return $count;
    }

    /**
     * Escape log content to prevent XSS when rendered in HTML.
     */
    private function escapeLogContent(string $content): string
    {
        $lines = explode("\n", rtrim($content));
        $escaped = array_map(function (string $line): string {
            $line = htmlspecialchars($line, ENT_QUOTES | ENT_HTML5, 'UTF-8');
            // Colorize log levels via span tags (safe, no user content)
            $line = preg_replace(
                '/(^|\s)(CRITICAL|ERROR|FATAL)(\s|$)/i',
                '$1<span class="log-error">$2</span>$3',
                $line
            );
            $line = preg_replace(
                '/(^|\s)(WARNING|WARN)(\s|$)/i',
                '$1<span class="log-warn">$2</span>$3',
                $line
            );
            $line = preg_replace(
                '/(^|\s)(INFO)(\s|$)/i',
                '$1<span class="log-info">$2</span>$3',
                $line
            );
            $line = preg_replace(
                '/(^|\s)(DEBUG)(\s|$)/i',
                '$1<span class="log-debug">$2</span>$3',
                $line
            );
            // Timestamps in brackets
            $line = preg_replace(
                '/(\[\d{4}-\d{2}-\d{2}[T\s]\d{2}:\d{2}:\d{2}[^\]]*\])/',
                '<span class="log-time">$1</span>',
                $line
            );
            return $line;
        }, $lines);

        return implode("\n", $escaped);
    }

    private function formatBytes(int $bytes): string
    {
        if ($bytes >= 1048576) {
            return round($bytes / 1048576, 1) . ' MB';
        }
        if ($bytes >= 1024) {
            return round($bytes / 1024, 1) . ' KB';
        }
        return $bytes . ' B';
    }
}
