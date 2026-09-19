<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Server-side validator. JavaScript validation is an enhancement only.
 */
final class Validator
{
    /** @var array<string, string> */
    private array $errors = [];
    /** @var array<string, mixed> */
    private array $validated = [];

    /**
     * @param array<string, mixed> $data
     * @param array<string, mixed> $rules
     * @param array<string, string> $messages
     */
    public function __construct(private array $data, private array $rules, private array $messages = [])
    {
        $this->run();
    }

    private function run(): void
    {
        foreach ($this->rules as $field => $ruleString) {
            $rules = is_array($ruleString) ? $ruleString : explode('|', (string) $ruleString);
            $value = $this->data[$field] ?? null;
            $isRequired = in_array(
                'required',
                array_map(static fn ($r) => trim(explode(':', (string) $r)[0]), $rules),
                true
            );

            $empty = $value === null || (is_string($value) && trim($value) === '') || $value === [];
            if ($isRequired && $empty) {
                $this->fail($field, 'required');
                continue;
            }
            if ($empty) {
                continue;
            }

            $passed = true;
            foreach ($rules as $rule) {
                if ($rule === '' || $rule === 'required') {
                    continue;
                }
                $parameter = null;
                if (str_contains($rule, ':')) {
                    [$rule, $parameter] = explode(':', $rule, 2);
                }
                $rule = trim($rule);
                if (!$this->check($rule, $value, $parameter, $field)) {
                    $this->fail($field, $rule, $parameter);
                    $passed = false;
                    break;
                }
            }

            if ($passed) {
                $this->validated[$field] = $value;
            }
        }
    }

    private function check(string $rule, mixed $value, ?string $parameter, string $field): bool
    {
        return match ($rule) {
            'email' => is_string($value) && filter_var($value, FILTER_VALIDATE_EMAIL) !== false,
            'min' => is_string($value)
                ? mb_strlen(trim($value)) >= (int) $parameter
                : (is_numeric($value) && (float) $value >= (float) $parameter),
            'max' => is_string($value)
                ? mb_strlen(trim($value)) <= (int) $parameter
                : (is_numeric($value) && (float) $value <= (float) $parameter),
            'min_value' => is_numeric($value) && (float) $value >= (float) $parameter,
            'max_value' => is_numeric($value) && (float) $value <= (float) $parameter,
            'numeric' => is_numeric($value),
            'integer' => filter_var($value, FILTER_VALIDATE_INT) !== false,
            'alpha_dash' => is_string($value) && preg_match('/^[A-Za-z0-9_-]+$/', $value) === 1,
            'slug' => is_string($value) && preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/', $value) === 1,
            'in' => in_array((string) $value, explode(',', (string) $parameter), true),
            'not_in' => !in_array((string) $value, explode(',', (string) $parameter), true),
            'same' => (string) $value === (string) ($this->data[$parameter] ?? null),
            'different' => (string) $value !== (string) ($this->data[$parameter] ?? null),
            'date' => is_string($value) && $this->isDate($value),
            'date_after' => $this->isDate((string) $value) && $this->isDate((string) ($this->data[$parameter] ?? '')),
            'url' => is_string($value) && filter_var($value, FILTER_VALIDATE_URL) !== false,
            'phone' => is_string($value) && preg_match('/^\+?[0-9\s\-()]{7,20}$/', $value) === 1,
            'boolean' => in_array(strtolower((string) $value), ['1', '0', 'true', 'false', 'on', 'off'], true),
            'array' => is_array($value),
            'confirmed' => (string) $value === (string) ($this->data[$field . '_confirmation'] ?? null),
            'exists' => $this->exists((string) $value, $parameter),
            'unique' => $this->unique($field, $value, $parameter),
            default => true,
        };
    }

    private function isDate(string $value): bool
    {
        $date = \DateTimeImmutable::createFromFormat('!Y-m-d', $value);
        return $date !== false && $date->format('Y-m-d') === $value;
    }

    private function exists(string $value, ?string $parameter): bool
    {
        if ($parameter === null) {
            return false;
        }
        [$table, $column] = array_pad(explode(',', $parameter), 2, 'id');
        return Database::count(
            sprintf('SELECT COUNT(*) FROM `%s` WHERE `%s` = ?', $this->ident($table), $this->ident($column)),
            [$value]
        ) > 0;
    }

    private function unique(string $field, mixed $value, ?string $parameter): bool
    {
        if ($parameter === null) {
            return false;
        }
        $parts = explode(',', $parameter);
        $sql = sprintf('SELECT COUNT(*) FROM `%s` WHERE `%s` = ?', $this->ident($parts[0]), $this->ident($parts[1] ?? $field));
        $params = [$value];

        if (isset($parts[2]) && $parts[2] !== '') {
            $sql .= ' AND `id` <> ?';
            $params[] = (int) $parts[2];
        }

        return Database::count($sql, $params) === 0;
    }

    private function ident(string $identifier): string
    {
        return preg_replace('/[^A-Za-z0-9_]/', '', $identifier) ?? '';
    }

    private function fail(string $field, string $rule, ?string $parameter = null): void
    {
        $this->errors[$field] = $this->messages[$field . '.' . $rule]
            ?? $this->messages[$field]
            ?? $this->defaultMessage($field, $rule, $parameter);
    }

    private function defaultMessage(string $field, string $rule, ?string $parameter): string
    {
        $label = ucwords(str_replace(['_', '.'], ' ', $field));

        return match ($rule) {
            'required' => $label . ' is required.',
            'email' => 'Enter a valid email address.',
            'min' => $label . ' must be at least ' . $parameter . ' characters.',
            'max' => $label . ' may not be longer than ' . $parameter . ' characters.',
            'min_value' => $label . ' must be at least ' . $parameter . '.',
            'max_value' => $label . ' may not be greater than ' . $parameter . '.',
            'numeric' => $label . ' must be a number.',
            'integer' => $label . ' must be a whole number.',
            'in' => $label . ' is not a valid selection.',
            'not_in' => $label . ' is not allowed.',
            'same' => $label . ' does not match.',
            'different' => $label . ' must be different.',
            'confirmed' => $label . ' confirmation does not match.',
            'date' => $label . ' must be a valid date.',
            'date_after' => $label . ' must fall after ' . $parameter . '.',
            'url' => $label . ' must be a valid URL.',
            'phone' => 'Enter a valid phone number.',
            'boolean' => $label . ' must be true or false.',
            'array' => $label . ' must be a list.',
            'exists' => $label . ' does not exist.',
            'unique' => $label . ' is already taken.',
            'alpha_dash' => $label . ' may only contain letters, numbers, dashes and underscores.',
            'slug' => $label . ' must be a valid slug.',
            default => $label . ' is invalid.',
        };
    }

    public function fails(): bool
    {
        return $this->errors !== [];
    }

    public function passes(): bool
    {
        return $this->errors === [];
    }

    /**
     * @return array<string, string>
     */
    public function errors(): array
    {
        return $this->errors;
    }

    public function firstError(): ?string
    {
        return $this->errors === [] ? null : (string) reset($this->errors);
    }

    /**
     * @return array<string, mixed>
     */
    public function validated(): array
    {
        return $this->validated;
    }
}