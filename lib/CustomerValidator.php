<?php

declare(strict_types=1);

final class CustomerValidator
{
    /**
     * @return array{valid: true, firstname: string, email: string, phone: string}|array{valid: false, errors: array<string, string>}
     */
    public static function validate(array $input): array
    {
        $errors = [];
        $firstname = trim((string) ($input['firstname'] ?? ''));
        $email = trim((string) ($input['email'] ?? ''));
        $phone = preg_replace('/\s+/', '', trim((string) ($input['phone'] ?? ''))) ?? '';

        if ($firstname === '') {
            $errors['firstname'] = 'First name is required';
        } elseif (mb_strlen($firstname) < 2) {
            $errors['firstname'] = 'First name must be at least 2 characters';
        } elseif (mb_strlen($firstname) > 60) {
            $errors['firstname'] = 'First name must not exceed 60 characters';
        } elseif (!preg_match("/^[A-Za-z][A-Za-z .'-]*$/u", $firstname)) {
            $errors['firstname'] = 'First name may only contain letters, spaces, dots and hyphens';
        }

        if ($email === '') {
            $errors['email'] = 'Email is required';
        } elseif (strlen($email) > 254) {
            $errors['email'] = 'Email must not exceed 254 characters';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = 'Enter a valid email address';
        }

        if ($phone === '') {
            $errors['phone'] = 'Phone number is required';
        } elseif (!preg_match('/^[6-9]\d{9}$/', $phone)) {
            $errors['phone'] = 'Enter a valid 10-digit Indian mobile number (starts with 6–9)';
        }

        if ($errors !== []) {
            return ['valid' => false, 'errors' => $errors];
        }

        return [
            'valid' => true,
            'firstname' => $firstname,
            'email' => $email,
            'phone' => $phone,
        ];
    }
}
