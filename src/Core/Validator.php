<?php
declare(strict_types=1);

namespace TurneroYa\Core;

/**
 * Validator estilo Laravel (subset).
 * Uso: Validator::make($data, ['email' => 'required|email'])
 */
final class Validator
{
    private array $errors = [];

    public function __construct(
        private readonly array $data,
        private readonly array $rules,
    ) {}

    public static function make(array $data, array $rules): self
    {
        $v = new self($data, $rules);
        $v->run();
        return $v;
    }

    public function run(): void
    {
        foreach ($this->rules as $field => $ruleStr) {
            $value = $this->data[$field] ?? null;
            $rules = explode('|', $ruleStr);
            foreach ($rules as $rule) {
                [$name, $param] = array_pad(explode(':', $rule, 2), 2, null);
                $method = 'validate' . ucfirst($name);
                if (method_exists($this, $method)) {
                    $this->$method($field, $value, $param);
                }
            }
        }
    }

    public function passes(): bool { return empty($this->errors); }
    public function fails(): bool { return !$this->passes(); }
    public function errors(): array { return $this->errors; }
    public function firstError(): ?string
    {
        foreach ($this->errors as $field => $msgs) return $msgs[0] ?? null;
        return null;
    }

    private function addError(string $field, string $msg): void
    {
        $this->errors[$field][] = $msg;
    }

    private function validateRequired(string $f, mixed $v): void
    {
        if ($v === null || $v === '' || (is_array($v) && empty($v))) {
            $this->addError($f, "El campo $f es obligatorio");
        }
    }

    private function validateEmail(string $f, mixed $v): void
    {
        if ($v && !filter_var($v, FILTER_VALIDATE_EMAIL)) {
            $this->addError($f, "El campo $f debe ser un email válido");
        }
    }

    private function validateMin(string $f, mixed $v, ?string $param): void
    {
        if ($v === null || $v === '') return;
        $min = (int) $param;
        $len = is_numeric($v) ? (float) $v : mb_strlen((string) $v);
        if ($len < $min) $this->addError($f, "El campo $f debe tener al menos $min caracteres");
    }

    private function validateMax(string $f, mixed $v, ?string $param): void
    {
        if ($v === null || $v === '') return;
        $max = (int) $param;
        $len = is_numeric($v) ? (float) $v : mb_strlen((string) $v);
        if ($len > $max) $this->addError($f, "El campo $f no debe superar $max caracteres");
    }

    private function validateNumeric(string $f, mixed $v): void
    {
        if ($v !== null && $v !== '' && !is_numeric($v)) {
            $this->addError($f, "El campo $f debe ser numérico");
        }
    }

    private function validateInteger(string $f, mixed $v): void
    {
        if ($v !== null && $v !== '' && filter_var($v, FILTER_VALIDATE_INT) === false) {
            $this->addError($f, "El campo $f debe ser un entero");
        }
    }

    private function validateIn(string $f, mixed $v, ?string $param): void
    {
        if ($v === null || $v === '') return;
        $options = explode(',', (string) $param);
        if (!in_array((string) $v, $options, true)) {
            $this->addError($f, "El campo $f debe ser uno de: " . implode(', ', $options));
        }
    }

    private function validateDate(string $f, mixed $v): void
    {
        if (!$v) return;
        try { new \DateTimeImmutable((string) $v); }
        catch (\Throwable) { $this->addError($f, "El campo $f debe ser una fecha válida"); }
    }

    private function validateConfirmed(string $f, mixed $v): void
    {
        $conf = $this->data[$f . '_confirmation'] ?? null;
        if ($v !== $conf) $this->addError($f, "El campo $f no coincide con la confirmación");
    }

    private function validateBool(string $f, mixed $v): void
    {
        if ($v === null) return;
        if (!in_array($v, [true, false, 0, 1, '0', '1', 'true', 'false', 'on', 'off'], true)) {
            $this->addError($f, "El campo $f debe ser booleano");
        }
    }

    private function validateRegex(string $f, mixed $v, ?string $param): void
    {
        if (!$v || !$param) return;
        if (!preg_match($param, (string) $v)) {
            $this->addError($f, "El campo $f tiene un formato inválido");
        }
    }
}
