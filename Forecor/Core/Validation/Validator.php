<?php

declare(strict_types=1);

namespace Forecor\Core\Validation;

class Validator
{
    private array $data;
    private array $rules;
    private array $messages;
    private array $errors = [];

    public function __construct(array $data, array $rules, array $messages = [])
    {
        $this->data = $data;
        $this->rules = $rules;
        $this->messages = $messages;
    }

    public static function make(array $data, array $rules, array $messages = []): self
    {
        return new self($data, $rules, $messages);
    }

    public function fails(): bool
    {
        $this->validate();
        return !empty($this->errors);
    }

    public function errors(): array
    {
        return $this->errors;
    }

    public function firstError(): ?string
    {
        if (empty($this->errors)) {
            return null;
        }
        $firstField = reset($this->errors);
        return is_array($firstField) ? reset($firstField) : $firstField;
    }

    protected function validate(): void
    {
        foreach ($this->rules as $field => $ruleString) {
            $rules = explode('|', $ruleString);

            foreach ($rules as $rule) {
                // Parse rule arguments, e.g., min:10, max:255, in:1,2,3
                $params = [];
                $ruleName = $rule;

                if (str_contains($rule, ':')) {
                    $parts = explode(':', $rule, 2);
                    $ruleName = $parts[0];
                    if (isset($parts[1])) {
                        $params = explode(',', $parts[1]);
                    }
                }

                $value = $this->data[$field] ?? null;

                // Stop validating this field if it's empty and not required
                if ($ruleName !== 'required' && ($value === null || $value === '')) {
                    continue;
                }

                $this->applyRule($field, $ruleName, $value, $params);

                // If there's an error for this field, don't process further rules for it
                if (isset($this->errors[$field])) {
                    break;
                }
            }
        }
    }

    protected function applyRule(string $field, string $ruleName, mixed $value, array $params): void
    {
        switch ($ruleName) {
            case 'required':
                if ($value === null || $value === '') {
                    $this->addError($field, 'required', "{$field} alanı zorunludur.");
                }
                break;

            case 'min':
                $min = (int)($params[0] ?? 0);
                if (is_string($value) && mb_strlen($value) < $min) {
                    $this->addError($field, 'min', "{$field} en az {$min} karakter olmalıdır.");
                } elseif (is_numeric($value) && (float)$value < $min) {
                    $this->addError($field, 'min', "{$field} en az {$min} olmalıdır.");
                }
                break;

            case 'max':
                $max = (int)($params[0] ?? 0);
                if (is_string($value) && mb_strlen($value) > $max) {
                    $this->addError($field, 'max', "{$field} en fazla {$max} karakter olmalıdır.");
                } elseif (is_numeric($value) && (float)$value > $max) {
                    $this->addError($field, 'max', "{$field} en fazla {$max} olmalıdır.");
                }
                break;

            case 'email':
                if (!filter_var($value, FILTER_VALIDATE_EMAIL)) {
                    $this->addError($field, 'email', "Geçerli bir e-posta adresi giriniz.");
                }
                break;

            case 'in':
                if (!in_array((string)$value, $params, true)) {
                    $this->addError($field, 'in', "Geçersiz {$field} seçimi.");
                }
                break;

            case 'url':
                if (!filter_var($value, FILTER_VALIDATE_URL)) {
                    $this->addError($field, 'url', "Geçerli bir URL giriniz.");
                }
                break;

            case 'numeric':
                if (!is_numeric($value)) {
                    $this->addError($field, 'numeric', "{$field} sadece sayısal bir değer olmalıdır.");
                }
                break;
        }
    }

    protected function addError(string $field, string $rule, string $defaultMessage): void
    {
        $customMessageKey = "{$field}.{$rule}";
        $this->errors[$field][] = $this->messages[$customMessageKey] ?? $this->messages[$field] ?? $defaultMessage;
    }
}
