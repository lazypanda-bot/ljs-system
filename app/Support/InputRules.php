<?php

namespace App\Support;

use Illuminate\Http\Request;

class InputRules
{
    /** Letters (including accents), spaces, apostrophes, hyphens and periods only. */
    public const NAME_PATTERN = "/^\\p{L}[\\p{L}\\p{M} .'\\-]*$/u";

    /** Philippine contact numbers must be exactly 11 digits, e.g. 09171234567. */
    public const PHONE_PATTERN = '/^[0-9]{11}$/';

    public static function name(bool $required = true): array
    {
        return [$required ? 'required' : 'nullable', 'string', 'min:2', 'max:100', 'regex:' . self::NAME_PATTERN];
    }

    public static function email(array $extra = []): array
    {
        return ['required', 'string', 'email:rfc', 'max:255', ...$extra];
    }

    public static function phone(bool $required = false): array
    {
        return [$required ? 'required' : 'nullable', 'string', 'regex:' . self::PHONE_PATTERN];
    }

    /** Trims text and strips spaces, dashes and brackets from phone numbers before validation. */
    public static function normalize(Request $request, array $nameFields = ['name'], array $phoneFields = []): void
    {
        $clean = [];
        foreach ($nameFields as $field) {
            if (is_string($request->input($field))) {
                $clean[$field] = trim(preg_replace('/\s+/', ' ', $request->input($field)));
            }
        }
        foreach ($phoneFields as $field) {
            if (is_string($request->input($field))) {
                $clean[$field] = preg_replace('/[\s\-().]/', '', $request->input($field));
            }
        }
        if (is_string($request->input('email'))) {
            $clean['email'] = trim($request->input('email'));
        }
        if (is_string($request->input('contact_email'))) {
            $clean['contact_email'] = trim($request->input('contact_email'));
        }
        $request->merge($clean);
    }

    public static function messages(): array
    {
        return [
            'regex' => 'The :attribute format is invalid.',
            'name.regex' => 'The name may only contain letters, spaces, apostrophes, hyphens and periods.',
            'contact_name.regex' => 'The name may only contain letters, spaces, apostrophes, hyphens and periods.',
            'contact_number.regex' => 'Enter a valid 11-digit contact number (e.g. 09171234567).',
            'phone.regex' => 'Enter a valid 11-digit phone number (e.g. 09171234567).',
        ];
    }
}
