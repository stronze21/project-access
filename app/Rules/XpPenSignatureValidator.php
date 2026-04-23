<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class XpPenSignatureValidator implements ValidationRule
{
    /**
     * Run the validation rule.
     *
     * @param  string  $attribute
     * @param  mixed  $value
     * @param  \Closure  $fail
     * @return void
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        // Check if the value is a data URL
        if (empty($value) || !is_string($value) || !str_starts_with($value, 'data:image/')) {
            $fail('The signature must be a valid image data URL.');
            return;
        }

        // Extract the base64 content without the data URL prefix
        $base64Data = explode(',', $value)[1] ?? null;

        if (empty($base64Data)) {
            $fail('The signature is not properly formatted.');
            return;
        }

        // Decode the base64 data to check if it's valid
        $decodedData = base64_decode($base64Data, true);

        if ($decodedData === false) {
            $fail('The signature contains invalid data.');
            return;
        }

        // Check the minimum data size (to prevent empty or near-empty signatures)
        // A valid signature should have some minimum amount of data (e.g., 100 bytes)
        if (strlen($decodedData) < 100) {
            $fail('The signature appears to be empty or too small. Please provide a complete signature.');
            return;
        }

        // Additional signature quality checks could be added here
    }
}
