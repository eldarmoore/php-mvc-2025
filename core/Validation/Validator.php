<?php

namespace Core\Validation;

/**
 * Validator Class
 *
 * Provides data validation with a fluent API and comprehensive
 * validation rules (required, email, min, max, etc.)
 */
class Validator
{
    /**
     * Data to validate
     */
    protected array $data;

    /**
     * Validation rules
     */
    protected array $rules;

    /**
     * Custom error messages
     */
    protected array $messages;

    /**
     * Validation errors
     */
    protected array $errors = [];

    /**
     * Validated data
     */
    protected array $validated = [];

    /**
     * Available validation rules
     */
    protected array $availableRules = [
        'required', 'email', 'min', 'max', 'numeric', 'integer',
        'string', 'alpha', 'alpha_num', 'in', 'not_in', 'url',
        'confirmed', 'same', 'different', 'regex', 'unique', 'exists'
    ];

    /**
     * Create a new validator instance
     *
     * @param array $data
     * @param array $rules
     * @param array $messages
     */
    public function __construct(array $data, array $rules, array $messages = [])
    {
        $this->data = $data;
        $this->rules = $rules;
        $this->messages = $messages;

        $this->validate();
    }

    /**
     * Perform validation
     *
     * @return void
     */
    protected function validate(): void
    {
        foreach ($this->rules as $field => $rules) {
            $rules = is_string($rules) ? explode('|', $rules) : $rules;
            $value = $this->data[$field] ?? null;

            foreach ($rules as $rule) {
                $this->validateRule($field, $value, $rule);
            }
        }
    }

    /**
     * Validate a single rule
     *
     * @param string $field
     * @param mixed $value
     * @param string $rule
     * @return void
     */
    protected function validateRule(string $field, $value, string $rule): void
    {
        // Parse rule and parameters (e.g., "min:5" => ['min', '5'])
        $parts = explode(':', $rule, 2);
        $ruleName = $parts[0];
        $parameters = isset($parts[1]) ? explode(',', $parts[1]) : [];

        // Check if rule method exists
        $method = 'validate' . ucfirst($ruleName);

        if (!method_exists($this, $method)) {
            return;
        }

        // Execute validation
        $passes = $this->$method($field, $value, $parameters);

        if (!$passes) {
            $this->addError($field, $ruleName, $parameters);
        } else {
            // Add to validated data if passes
            if (!isset($this->validated[$field])) {
                $this->validated[$field] = $value;
            }
        }
    }

    /**
     * Validate required rule
     *
     * @param string $field
     * @param mixed $value
     * @param array $parameters
     * @return bool
     */
    protected function validateRequired(string $field, $value, array $parameters): bool
    {
        if ($value === null || $value === '') {
            return false;
        }

        if (is_array($value) && empty($value)) {
            return false;
        }

        return true;
    }

    /**
     * Validate email rule
     *
     * @param string $field
     * @param mixed $value
     * @param array $parameters
     * @return bool
     */
    protected function validateEmail(string $field, $value, array $parameters): bool
    {
        if ($value === null || $value === '') {
            return true; // Use 'required' for non-null validation
        }

        return filter_var($value, FILTER_VALIDATE_EMAIL) !== false;
    }

    /**
     * Validate min rule
     *
     * @param string $field
     * @param mixed $value
     * @param array $parameters
     * @return bool
     */
    protected function validateMin(string $field, $value, array $parameters): bool
    {
        $min = $parameters[0] ?? 0;

        if (is_numeric($value)) {
            return $value >= $min;
        }

        if (is_string($value)) {
            return mb_strlen($value) >= $min;
        }

        if (is_array($value)) {
            return count($value) >= $min;
        }

        return false;
    }

    /**
     * Validate max rule
     *
     * @param string $field
     * @param mixed $value
     * @param array $parameters
     * @return bool
     */
    protected function validateMax(string $field, $value, array $parameters): bool
    {
        $max = $parameters[0] ?? 0;

        if (is_numeric($value)) {
            return $value <= $max;
        }

        if (is_string($value)) {
            return mb_strlen($value) <= $max;
        }

        if (is_array($value)) {
            return count($value) <= $max;
        }

        return false;
    }

    /**
     * Validate numeric rule
     *
     * @param string $field
     * @param mixed $value
     * @param array $parameters
     * @return bool
     */
    protected function validateNumeric(string $field, $value, array $parameters): bool
    {
        if ($value === null || $value === '') {
            return true;
        }

        return is_numeric($value);
    }

    /**
     * Validate integer rule
     *
     * @param string $field
     * @param mixed $value
     * @param array $parameters
     * @return bool
     */
    protected function validateInteger(string $field, $value, array $parameters): bool
    {
        if ($value === null || $value === '') {
            return true;
        }

        return filter_var($value, FILTER_VALIDATE_INT) !== false;
    }

    /**
     * Validate string rule
     *
     * @param string $field
     * @param mixed $value
     * @param array $parameters
     * @return bool
     */
    protected function validateString(string $field, $value, array $parameters): bool
    {
        if ($value === null || $value === '') {
            return true;
        }

        return is_string($value);
    }

    /**
     * Validate alpha rule (letters only)
     *
     * @param string $field
     * @param mixed $value
     * @param array $parameters
     * @return bool
     */
    protected function validateAlpha(string $field, $value, array $parameters): bool
    {
        if ($value === null || $value === '') {
            return true;
        }

        return ctype_alpha($value);
    }

    /**
     * Validate alpha_num rule (letters and numbers)
     *
     * @param string $field
     * @param mixed $value
     * @param array $parameters
     * @return bool
     */
    protected function validateAlphaNum(string $field, $value, array $parameters): bool
    {
        if ($value === null || $value === '') {
            return true;
        }

        return ctype_alnum($value);
    }

    /**
     * Validate in rule
     *
     * @param string $field
     * @param mixed $value
     * @param array $parameters
     * @return bool
     */
    protected function validateIn(string $field, $value, array $parameters): bool
    {
        if ($value === null || $value === '') {
            return true;
        }

        return in_array($value, $parameters);
    }

    /**
     * Validate not_in rule
     *
     * @param string $field
     * @param mixed $value
     * @param array $parameters
     * @return bool
     */
    protected function validateNotIn(string $field, $value, array $parameters): bool
    {
        if ($value === null || $value === '') {
            return true;
        }

        return !in_array($value, $parameters);
    }

    /**
     * Validate URL rule
     *
     * @param string $field
     * @param mixed $value
     * @param array $parameters
     * @return bool
     */
    protected function validateUrl(string $field, $value, array $parameters): bool
    {
        if ($value === null || $value === '') {
            return true;
        }

        return filter_var($value, FILTER_VALIDATE_URL) !== false;
    }

    /**
     * Validate confirmed rule (for password confirmation)
     *
     * @param string $field
     * @param mixed $value
     * @param array $parameters
     * @return bool
     */
    protected function validateConfirmed(string $field, $value, array $parameters): bool
    {
        $confirmationField = $field . '_confirmation';
        return isset($this->data[$confirmationField]) && $value === $this->data[$confirmationField];
    }

    /**
     * Validate same rule
     *
     * @param string $field
     * @param mixed $value
     * @param array $parameters
     * @return bool
     */
    protected function validateSame(string $field, $value, array $parameters): bool
    {
        $otherField = $parameters[0] ?? null;

        if (!$otherField) {
            return false;
        }

        return isset($this->data[$otherField]) && $value === $this->data[$otherField];
    }

    /**
     * Validate different rule
     *
     * @param string $field
     * @param mixed $value
     * @param array $parameters
     * @return bool
     */
    protected function validateDifferent(string $field, $value, array $parameters): bool
    {
        $otherField = $parameters[0] ?? null;

        if (!$otherField) {
            return false;
        }

        return !isset($this->data[$otherField]) || $value !== $this->data[$otherField];
    }

    /**
     * Validate regex rule
     *
     * @param string $field
     * @param mixed $value
     * @param array $parameters
     * @return bool
     */
    protected function validateRegex(string $field, $value, array $parameters): bool
    {
        if ($value === null || $value === '') {
            return true;
        }

        $pattern = $parameters[0] ?? null;

        if (!$pattern) {
            return false;
        }

        return preg_match($pattern, $value) === 1;
    }

    /**
     * Add an error
     *
     * @param string $field
     * @param string $rule
     * @param array $parameters
     * @return void
     */
    protected function addError(string $field, string $rule, array $parameters): void
    {
        $message = $this->getMessage($field, $rule, $parameters);
        $this->errors[$field][] = $message;
    }

    /**
     * Get error message for a rule
     *
     * @param string $field
     * @param string $rule
     * @param array $parameters
     * @return string
     */
    protected function getMessage(string $field, string $rule, array $parameters): string
    {
        // Check for custom message
        $key = "{$field}.{$rule}";
        if (isset($this->messages[$key])) {
            return $this->messages[$key];
        }

        // Default messages
        $messages = [
            'required' => "The {$field} field is required.",
            'email' => "The {$field} must be a valid email address.",
            'min' => "The {$field} must be at least {$parameters[0]}.",
            'max' => "The {$field} may not be greater than {$parameters[0]}.",
            'numeric' => "The {$field} must be a number.",
            'integer' => "The {$field} must be an integer.",
            'string' => "The {$field} must be a string.",
            'alpha' => "The {$field} may only contain letters.",
            'alpha_num' => "The {$field} may only contain letters and numbers.",
            'in' => "The selected {$field} is invalid.",
            'not_in' => "The selected {$field} is invalid.",
            'url' => "The {$field} must be a valid URL.",
            'confirmed' => "The {$field} confirmation does not match.",
            'same' => "The {$field} and {$parameters[0]} must match.",
            'different' => "The {$field} and {$parameters[0]} must be different.",
            'regex' => "The {$field} format is invalid.",
        ];

        return $messages[$rule] ?? "The {$field} is invalid.";
    }

    /**
     * Check if validation failed
     *
     * @return bool
     */
    public function fails(): bool
    {
        return !empty($this->errors);
    }

    /**
     * Check if validation passed
     *
     * @return bool
     */
    public function passes(): bool
    {
        return empty($this->errors);
    }

    /**
     * Get all errors
     *
     * @return array
     */
    public function errors(): array
    {
        return $this->errors;
    }

    /**
     * Get validated data
     *
     * @return array
     */
    public function validated(): array
    {
        return $this->validated;
    }
}
