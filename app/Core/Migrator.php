<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Lightweight, checksum-aware migration runner.
 *
 * Safety rules:
 *  - Migrations are executed at most once (tracked in `schema_migrations`).
 *  - Changed checksums of already-applied migrations are reported, never silently re-run.
 *  - There is intentionally NO migrate:fresh / database:reset command.
 */
final class Migrator
{
    private string $path;

    public function __construct(?string $path = null)
    {
        $this->path = $path ?? dirname(__DIR__, 2) . '/database/migrations';
    }

    public function ensureRepository(): void
    {
        Database::query(
            'CREATE TABLE IF NOT EXISTS `schema_migrations` (
                `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                `migration` VARCHAR(255) NOT NULL,
                `batch` INT UNSIGNED NOT NULL DEFAULT 1,
                `checksum` CHAR(64) NOT NULL,
                `statements` INT UNSIGNED NOT NULL DEFAULT 0,
                `executed_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (`id`),
                UNIQUE KEY `uniq_schema_migrations_migration` (`migration`),
                KEY `idx_schema_migrations_batch` (`batch`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
        );
    }

    /**
     * @return array<string, string> migration file name => absolute path
     */
    public function available(): array
    {
        $files = glob($this->path . '/*.sql') ?: [];
        sort($files, SORT_STRING);

        $map = [];
        foreach ($files as $file) {
            $map[basename($file)] = $file;
        }
        return $map;
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    public function applied(): array
    {
        $rows = Database::select('SELECT * FROM `schema_migrations` ORDER BY `id`');
        $map = [];
        foreach ($rows as $row) {
            $map[(string) $row['migration']] = $row;
        }
        return $map;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function status(): array
    {
        $this->ensureRepository();
        $applied = $this->applied();
        $available = $this->available();
        $report = [];

        foreach ($available as $name => $file) {
            $checksum = hash_file('sha256', $file) ?: '';
            $row = $applied[$name] ?? null;

            $state = 'pending';
            if ($row !== null) {
                $state = hash_equals((string) $row['checksum'], $checksum) ? 'applied' : 'modified';
            }

            $report[] = [
                'migration' => $name,
                'state' => $state,
                'batch' => $row['batch'] ?? null,
                'executed_at' => $row['executed_at'] ?? null,
                'checksum' => $checksum,
            ];
        }

        foreach ($applied as $name => $row) {
            if (!isset($available[$name])) {
                $report[] = [
                    'migration' => $name,
                    'state' => 'missing',
                    'batch' => $row['batch'],
                    'executed_at' => $row['executed_at'],
                    'checksum' => $row['checksum'],
                ];
            }
        }

        return $report;
    }

    public function unappliedCount(): int
    {
        return count(array_filter($this->status(), static fn (array $r): bool => $r['state'] === 'pending'));
    }

    /**
     * Apply all pending migrations.
     *
     * @return array{pending:int, applied:array<int,string>, modified:array<int,string>, failed:array<int,string>}
     */
    public function migrate(bool $dryRun = false, ?callable $logger = null): array
    {
        $this->ensureRepository();

        $applied = $this->applied();
        $available = $this->available();

        $batch = 1;
        if ($applied !== []) {
            $batch = ((int) max(array_map(static fn (array $r): int => (int) $r['batch'], $applied))) + 1;
        }

        $result = ['pending' => 0, 'applied' => [], 'modified' => [], 'failed' => []];
        $pending = [];

        foreach ($available as $name => $file) {
            if (isset($applied[$name])) {
                $checksum = hash_file('sha256', $file) ?: '';
                if (!hash_equals((string) $applied[$name]['checksum'], $checksum)) {
                    $result['modified'][] = $name;
                }
                continue;
            }
            $pending[$name] = $file;
        }

        $result['pending'] = count($pending);

        if ($result['modified'] !== []) throw new \RuntimeException('Applied migration checksum changed; refusing further migrations.');

        if ($dryRun) {
            return $result;
        }

        foreach ($pending as $name => $file) {
            $sql = (string) file_get_contents($file);
            $statements = SqlScript::split($sql);

            try {
                $executed = $this->runStatements($statements);
                Database::insert('schema_migrations', [
                    'migration' => $name,
                    'batch' => $batch,
                    'checksum' => hash_file('sha256', $file) ?: '',
                    'statements' => $executed,
                    'executed_at' => gmdate('Y-m-d H:i:s'),
                ]);
                $result['applied'][] = $name;
                Logger::info('migration.applied', ['migration' => $name, 'statements' => $executed]);
                if ($logger !== null) {
                    $logger(sprintf('  ok  %s (%d statements)', $name, $executed));
                }
            } catch (\Throwable $e) {
                $result['failed'][] = $name;
                Logger::critical('migration.failed', ['migration' => $name, 'error' => $e->getMessage()]);
                throw $e;
            }
        }

        return $result;
    }

    /**
     * MySQL implicitly commits DDL, so statements run individually: this gives the
     * clearest failure point and keeps the process restartable.
     *
     * @param array<int, string> $statements
     */
    private function runStatements(array $statements): int
    {
        $count = 0;
        foreach ($statements as $statement) {
            $statement = trim($statement);
            if ($statement === '') {
                continue;
            }
            Database::connection()->exec($statement);
            $count++;
        }
        return $count;
    }
}