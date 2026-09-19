<?php

declare(strict_types=1);

namespace App\Database;

use App\Core\Database;
use App\Core\Logger;
use RuntimeException;

/**
 * Lightweight, checksum-aware SQL migration runner.
 *
 * Deliberately has NO destructive "fresh"/"reset" capability. Migrations are
 * additive-only by convention and every applied migration is recorded with a
 * checksum so accidental edits after execution are detected.
 */
final class Migrator
{
    public function __construct(private string $directory)
    {
        if (!is_dir($this->directory)) {
            throw new RuntimeException('Migration directory not found: ' . $this->directory);
        }
    }

    public function ensureRepository(): void
    {
        Database::query(
            'CREATE TABLE IF NOT EXISTS `schema_migrations` (
                `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                `migration` VARCHAR(191) NOT NULL,
                `batch` INT UNSIGNED NOT NULL,
                `checksum` CHAR(64) NOT NULL,
                `statements` INT UNSIGNED NOT NULL DEFAULT 0,
                `duration_ms` INT UNSIGNED NOT NULL DEFAULT 0,
                `executed_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (`id`),
                UNIQUE KEY `uniq_migration` (`migration`),
                KEY `idx_batch` (`batch`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
        );
    }

    /**
     * @return array<int, string> pending migration basenames in order
     */
    public function pending(): array
    {
        $this->ensureRepository();
        $applied = $this->appliedMap();

        return array_values(array_diff($this->files(), array_keys($applied)));
    }

    /**
     * @return array<string, array{migration: string, batch: int, checksum: string, executed_at: string}>
     */
    public function appliedMap(): array
    {
        $rows = Database::select('SELECT migration, batch, checksum, executed_at FROM schema_migrations ORDER BY id');
        $map = [];
        foreach ($rows as $row) {
            $map[(string) $row['migration']] = [
                'migration' => (string) $row['migration'],
                'batch' => (int) $row['batch'],
                'checksum' => (string) $row['checksum'],
                'executed_at' => (string) $row['executed_at'],
            ];
        }
        return $map;
    }

    /**
     * @return array<int, string>
     */
    public function files(): array
    {
        $files = glob($this->directory . '/*.sql') ?: [];
        $names = array_map(static fn (string $f): string => basename($f), $files);
        sort($names, SORT_NATURAL);
        return array_values($names);
    }

    /**
     * Detect migrations edited after they were executed.
     *
     * @return array<int, string>
     */
    public function checksumDrift(): array
    {
        $drifted = [];
        foreach ($this->appliedMap() as $name => $meta) {
            $path = $this->directory . '/' . $name;
            if (!is_file($path)) {
                $drifted[] = $name . ' (missing on disk)';
                continue;
            }
            if (hash_file('sha256', $path) !== $meta['checksum']) {
                $drifted[] = $name . ' (checksum changed after execution)';
            }
        }
        return $drifted;
    }

    /**
     * @return array<int, array{file: string, statements: int, duration_ms: int}>
     */
    public function run(?int $batch = null): array
    {
        $this->ensureRepository();

        $drift = $this->checksumDrift();
        if ($drift !== []) {
            throw new RuntimeException(
                "Refusing to migrate: already-executed migrations were modified:\n - " . implode("\n - ", $drift)
            );
        }

        $pending = $this->pending();
        if ($pending === []) {
            return [];
        }

        $batch ??= (int) Database::count('SELECT COALESCE(MAX(batch), 0) FROM schema_migrations') + 1;
        $results = [];

        foreach ($pending as $file) {
            $path = $this->directory . '/' . $file;
            $statements = $this->splitStatements((string) file_get_contents($path));
            $started = microtime(true);

            foreach ($statements as $index => $statement) {
                try {
                    Database::connection()->exec($statement);
                } catch (\Throwable $e) {
                    throw new RuntimeException(
                        sprintf('Migration %s failed at statement #%d: %s', $file, $index + 1, $e->getMessage()),
                        0,
                        $e
                    );
                }
            }

            $duration = (int) round((microtime(true) - $started) * 1000);
            Database::insert('schema_migrations', [
                'migration' => $file,
                'batch' => $batch,
                'checksum' => (string) hash_file('sha256', $path),
                'statements' => count($statements),
                'duration_ms' => $duration,
            ]);

            Logger::info('migration.applied', ['migration' => $file, 'batch' => $batch, 'duration_ms' => $duration]);
            $results[] = ['file' => $file, 'statements' => count($statements), 'duration_ms' => $duration];
        }

        return $results;
    }

    /**
     * Split a SQL file into executable statements.
     *
     * Handles line comments (-- and #), block comments, quoted strings that
     * contain semicolons, and DELIMITER blocks (used for triggers/routines).
     *
     * @return array<int, string>
     */
    public function splitStatements(string $sql): array
    {
        $statements = [];
        $buffer = '';
        $length = strlen($sql);
        $inSingle = $inDouble = $inBacktick = false;
        $inLineComment = $inBlockComment = false;
        $delimiter = ';';
        $i = 0;

        while ($i < $length) {
            $char = $sql[$i];
            $next = $i + 1 < $length ? $sql[$i + 1] : '';

            if ($inLineComment) {
                if ($char === "\n") {
                    $inLineComment = false;
                    $buffer .= "\n";
                }
                $i++;
                continue;
            }

            if ($inBlockComment) {
                if ($char === '*' && $next === '/') {
                    $inBlockComment = false;
                    $i += 2;
                    continue;
                }
                $i++;
                continue;
            }

            if (!$inSingle && !$inDouble && !$inBacktick) {
                if ($char === '-' && $next === '-') {
                    $inLineComment = true;
                    $i += 2;
                    continue;
                }
                if ($char === '#') {
                    $inLineComment = true;
                    $i++;
                    continue;
                }
                if ($char === '/' && $next === '*') {
                    $inBlockComment = true;
                    $i += 2;
                    continue;
                }
                if (preg_match('/^\s*DELIMITER\s+(\S+)/i', substr($sql, $i, 40), $m) === 1) {
                    $lineEnd = strpos($sql, "\n", $i);
                    $lineEnd = $lineEnd === false ? $length : $lineEnd;
                    $delimiter = $m[1];
                    $i = $lineEnd + 1;
                    continue;
                }
            }

            if ($char === "'" && !$inDouble && !$inBacktick) {
                if ($inSingle && $next === "'") {
                    $buffer .= "''";
                    $i += 2;
                    continue;
                }
                $inSingle = !$inSingle;
            } elseif ($char === '"' && !$inSingle && !$inBacktick) {
                $inDouble = !$inDouble;
            } elseif ($char === '`' && !$inSingle && !$inDouble) {
                $inBacktick = !$inBacktick;
            } elseif (!$inSingle && !$inDouble && !$inBacktick) {
                if (substr($sql, $i, strlen($delimiter)) === $delimiter) {
                    $statement = trim($buffer);
                    if ($statement !== '') {
                        $statements[] = $statement;
                    }
                    $buffer = '';
                    $i += strlen($delimiter);
                    continue;
                }
            }

            $buffer .= $char;
            $i++;
        }

        $final = trim($buffer);
        if ($final !== '') {
            $statements[] = $final;
        }

        return $statements;
    }
}