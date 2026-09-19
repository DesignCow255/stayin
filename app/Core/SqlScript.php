<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Splits a .sql file into executable statements.
 *
 * Handles:
 *  - line comments (-- and #)
 *  - block comments
 *  - single/double quoted strings with escapes
 *  - DELIMITER directives (for triggers/procedures)
 */
final class SqlScript
{
    /**
     * @return array<int, string>
     */
    public static function split(string $sql): array
    {
        $statements = [];
        $buffer = '';
        $length = strlen($sql);
        $i = 0;
        $delimiter = ';';
        $inSingle = false;
        $inDouble = false;
        $inBacktick = false;

        while ($i < $length) {
            $char = $sql[$i];
            $next = $i + 1 < $length ? $sql[$i + 1] : '';

            // Comments (only outside of strings)
            if (!$inSingle && !$inDouble && !$inBacktick) {
                if (($char === '-' && $next === '-') || $char === '#') {
                    $eol = strpos($sql, "\n", $i);
                    $i = $eol === false ? $length : $eol + 1;
                    continue;
                }
                if ($char === '/' && $next === '*') {
                    $end = strpos($sql, '*/', $i + 2);
                    $i = $end === false ? $length : $end + 2;
                    continue;
                }
            }

            if ($char === "'" && !$inDouble && !$inBacktick) {
                if ($inSingle && $next === "'") { // escaped '' inside a string
                    $buffer .= "''";
                    $i += 2;
                    continue;
                }
                $inSingle = !$inSingle;
                $buffer .= $char;
                $i++;
                continue;
            }

            if ($char === '"' && !$inSingle && !$inBacktick) {
                $inDouble = !$inDouble;
                $buffer .= $char;
                $i++;
                continue;
            }

            if ($char === '`' && !$inSingle && !$inDouble) {
                $inBacktick = !$inBacktick;
                $buffer .= $char;
                $i++;
                continue;
            }

            // DELIMITER directive
            if (!$inSingle && !$inDouble && !$inBacktick && stripos(substr($sql, $i, 9), 'DELIMITER') === 0) {
                $eol = strpos($sql, "\n", $i);
                $line = substr($sql, $i, ($eol === false ? $length : $eol) - $i);
                $delimiter = trim(substr($line, 9)) ?: ';';
                $i = $eol === false ? $length : $eol + 1;
                continue;
            }

            if (!$inSingle && !$inDouble && !$inBacktick && substr($sql, $i, strlen($delimiter)) === $delimiter) {
                $statements[] = trim($buffer);
                $buffer = '';
                $i += strlen($delimiter);
                continue;
            }

            $buffer .= $char;
            $i++;
        }

        if (trim($buffer) !== '') {
            $statements[] = trim($buffer);
        }

        return array_values(array_filter($statements, static fn (string $s): bool => $s !== ''));
    }
}