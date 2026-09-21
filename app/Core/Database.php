<?php

declare(strict_types=1);

namespace App\Core;

use PDO;
use PDOException;
use PDOStatement;
use RuntimeException;

/**
 * Thin PDO wrapper. All SQL in the application flows through here so that
 * prepared statements, transactions and query counting are enforced in one place.
 */
final class Database
{
    private static ?PDO $pdo = null;
    private static int $queryCount = 0;
    private static float $queryTime = 0.0;
    private static int $transactionDepth = 0;

    public static function connection(): PDO
    {
        if (self::$pdo instanceof PDO) {
            return self::$pdo;
        }

        $driver = Config::string('database.driver', 'mysql');
        if ($driver !== 'mysql') {
            throw new RuntimeException('Unsupported database driver: ' . $driver);
        }

        $socket = Config::string('database.socket', '');
        if ($socket !== '' && file_exists($socket)) {
            $dsn = sprintf(
                'mysql:unix_socket=%s;dbname=%s;charset=%s',
                $socket,
                Config::string('database.database'),
                Config::string('database.charset', 'utf8mb4')
            );
        } else {
            $dsn = sprintf(
                'mysql:host=%s;port=%s;dbname=%s;charset=%s',
                Config::string('database.host', '127.0.0.1'),
                Config::string('database.port', '3306'),
                Config::string('database.database'),
                Config::string('database.charset', 'utf8mb4')
            );
        }

        try {
            self::$pdo = new PDO(
                $dsn,
                Config::string('database.username'),
                Config::string('database.password'),
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false,
                    PDO::ATTR_STRINGIFY_FETCHES => false,
                    PDO::MYSQL_ATTR_INIT_COMMAND => "SET time_zone = '+00:00'",
                ]
            );
        } catch (PDOException $e) {
            Logger::critical('database.connection_failed', ['message' => $e->getMessage()]);
            throw new RuntimeException('Database connection could not be established.', 0, $e);
        }

        return self::$pdo;
    }

    /**
     * @param array<string|int, mixed> $params
     */
    public static function query(string $sql, array $params = []): PDOStatement
    {
        $started = microtime(true);
        try {
            $statement = self::connection()->prepare($sql);
            $statement->execute($params);
        } catch (PDOException $e) {
            Logger::error('database.query_failed', [
                'sql' => self::redact($sql),
                'message' => $e->getMessage(),
            ]);
            throw $e;
        } finally {
            self::$queryCount++;
            self::$queryTime += microtime(true) - $started;
        }

        return $statement;
    }

    /**
     * @param array<string|int, mixed> $params
     * @return array<string, mixed>|null
     */
    public static function first(string $sql, array $params = []): ?array
    {
        $row = self::query($sql, $params)->fetch();
        return $row === false ? null : $row;
    }

    /**
     * @param array<string|int, mixed> $params
     * @return array<int, array<string, mixed>>
     */
    public static function select(string $sql, array $params = []): array
    {
        return self::query($sql, $params)->fetchAll();
    }

    /**
     * @param array<string|int, mixed> $params
     */
    public static function scalar(string $sql, array $params = []): mixed
    {
        $value = self::query($sql, $params)->fetchColumn();
        return $value === false ? null : $value;
    }

    /**
     * @param array<string|int, mixed> $params
     */
    public static function count(string $sql, array $params = []): int
    {
        return (int) self::scalar($sql, $params);
    }

    /**
     * @param array<string|int, mixed> $params
     */
    public static function execute(string $sql, array $params = []): int
    {
        return self::query($sql, $params)->rowCount();
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function insert(string $table, array $data): int
    {
        $columns = array_keys($data);
        $wrapped = implode(', ', array_map(static fn (string $c): string => '`' . $c . '`', $columns));
        $placeholders = implode(', ', array_map(static fn (string $c): string => ':' . $c, $columns));

        self::query(sprintf('INSERT INTO `%s` (%s) VALUES (%s)', $table, $wrapped, $placeholders), self::bindable($data));

        return (int) self::connection()->lastInsertId();
    }

    /**
     * @param array<string, mixed> $data
     * @param array<string, mixed> $where
     */
    public static function update(string $table, array $data, array $where): int
    {
        if ($data === [] || $where === []) {
            return 0;
        }

        $sets = [];
        $params = [];
        foreach ($data as $column => $value) {
            $sets[] = '`' . $column . '` = :s_' . $column;
            $params['s_' . $column] = $value;
        }
        $conditions = [];
        foreach ($where as $column => $value) {
            $conditions[] = '`' . $column . '` = :w_' . $column;
            $params['w_' . $column] = $value;
        }

        $sql = sprintf('UPDATE `%s` SET %s WHERE %s', $table, implode(', ', $sets), implode(' AND ', $conditions));

        return self::execute($sql, self::bindable($params));
    }

    /**
     * Execute a closure inside a transaction, supporting safe nesting via savepoints.
     *
     * @template T
     * @param callable(PDO):T $callback
     * @return T
     */
    public static function transaction(callable $callback): mixed
    {
        $pdo = self::connection();
        $savepoint = null;

        if (self::$transactionDepth === 0) {
            $pdo->beginTransaction();
        } else {
            $savepoint = 'sp_' . bin2hex(random_bytes(4));
            $pdo->exec('SAVEPOINT ' . $savepoint);
        }
        self::$transactionDepth++;

        try {
            $result = $callback($pdo);
            self::$transactionDepth--;
            if (self::$transactionDepth === 0) {
                $pdo->commit();
            } elseif ($savepoint !== null) {
                $pdo->exec('RELEASE SAVEPOINT ' . $savepoint);
            }
            return $result;
        } catch (\Throwable $e) {
            self::$transactionDepth--;
            if (self::$transactionDepth === 0 && $pdo->inTransaction()) {
                $pdo->rollBack();
            } elseif ($savepoint !== null && $pdo->inTransaction()) {
                $pdo->exec('ROLLBACK TO SAVEPOINT ' . $savepoint);
            }
            throw $e;
        }
    }

    /**
     * Row-level lock. MUST be called inside a transaction.
     *
     * @param array<string|int, mixed> $params
     * @return array<string, mixed>|null
     */
    public static function lockFirst(string $sql, array $params = []): ?array
    {
        $sql = rtrim(rtrim($sql), ';');
        if (!str_contains(strtoupper($sql), 'FOR UPDATE')) {
            $sql .= ' FOR UPDATE';
        }
        $row = self::query($sql, $params)->fetch();
        return $row === false ? null : $row;
    }

    /**
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    private static function bindable(array $data): array
    {
        foreach ($data as $key => $value) {
            if (is_bool($value)) {
                $data[$key] = $value ? 1 : 0;
            } elseif ($value instanceof \DateTimeInterface) {
                $data[$key] = $value->format('Y-m-d H:i:s');
            } elseif (is_array($value) || is_object($value)) {
                $data[$key] = json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            }
        }
        return $data;
    }

    public static function queryCount(): int
    {
        return self::$queryCount;
    }

    public static function queryTime(): float
    {
        return self::$queryTime;
    }

    public static function resetMetrics(): void
    {
        self::$queryCount = 0;
        self::$queryTime = 0.0;
    }

    public static function disconnect(): void
    {
        self::$pdo = null;
        self::$transactionDepth = 0;
    }

    private static function redact(string $sql): string
    {
        return preg_replace('/\s+/', ' ', mb_substr($sql, 0, 500)) ?? $sql;
    }
}