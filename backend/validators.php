<?php
// validators.php — reusable input validation functions.

function validate_name(string $value, int $maxLength = 30): bool
{
    return (bool) preg_match("/^[A-Za-z'\- ]{1,$maxLength}$/", $value);
}

function validate_mid_init(string $value): bool
{
    return $value === '' || (bool) preg_match("/^[A-Za-z]{1,2}$/", $value);
}

function validate_date_not_future(string $value): bool
{
    if (!preg_match("/^\d{4}-\d{2}-\d{2}$/", $value)) {
        return false;
    }
    $inputDate = strtotime($value);
    $today = strtotime(date('Y-m-d'));
    return $inputDate !== false && $inputDate <= $today;
}

function validate_optional_date(string $value): bool
{
    return $value === '' || validate_date_not_future($value);
}

function validate_phone(string $value): bool
{
    return (bool) preg_match('/^[0-9()\-\s]{7,20}$/', $value);
}

function validate_email(string $value): bool
{
    return filter_var($value, FILTER_VALIDATE_EMAIL) !== false;
}

function validate_in_list(string $value, array $allowed): bool
{
    return in_array($value, $allowed, true);
}
