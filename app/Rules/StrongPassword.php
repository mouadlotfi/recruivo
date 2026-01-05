<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class StrongPassword implements ValidationRule
{
    protected ?string $username;

    /**
     * Create a new rule instance.
     *
     * @param  string|null  $username  The username to check against
     */
    public function __construct(?string $username = null)
    {
        $this->username = $username;
    }

    /**
     * Run the validation rule.
     *
     * @param  \Closure(string, ?string=): \Illuminate\Translation\PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        // Check length: 12-64 characters
        if (strlen($value) < 12 || strlen($value) > 64) {
            $fail(__('validation.password_length'));
            return;
        }

        // Check if password contains username (case-insensitive)
        if ($this->username && stripos($value, $this->username) !== false) {
            $fail(__('validation.password_no_username'));
            return;
        }

        // Check character types
        $hasUpperCase = preg_match('/[A-Z]/', $value);
        $hasLowerCase = preg_match('/[a-z]/', $value);
        $hasNumber = preg_match('/[0-9]/', $value);
        $hasSymbol = preg_match('/[^A-Za-z0-9]/', $value);

        $typesCount = $hasUpperCase + $hasLowerCase + $hasNumber + $hasSymbol;

        if ($typesCount < 3) {
            $fail(__('validation.password_complexity'));
            return;
        }
    }
}
