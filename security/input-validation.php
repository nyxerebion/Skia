<?php

function validateUsername($input, &$error = null)
{
    $length = strlen($input);
    if ($length < 4) {
        $error = 'Username must be at least 4 characters';
        return false;
    }
    if ($length > 12) {
        $error = 'Username must be at most 12 characters';
        return false;
    }
    if (!preg_match('/^[a-zA-Z0-9_]+$/', $input)) {
        $error = 'Username can only contain letters, numbers, and underscore';
        return false;
    }
    if (substr_count($input, '_') > 1) {
        $error = 'Username can have at most one underscore';
        return false;
    }
    if (!preg_match('/\d/', $input)) {
        $error = 'Username must contain at least one number';
        return false;
    }
    return true;
}

/**
 * Validate email
 */
function validateEmail($input, &$error = null)
{
    if (!filter_var($input, FILTER_VALIDATE_EMAIL)) {
        $error = 'Invalid email format';
        return false;
    }
    return true;
}

/**
 * Validate password
 */
function validatePassword($input, &$error = null)
{
    $length = strlen($input);
    if ($length < 6) {
        $error = 'Password must be at least 6 characters';
        return false;
    }
    if ($length > 20) {
        $error = 'Password must be at most 20 characters';
        return false;
    }
    return true;
}

/**
 * Validate password confirmation
 */
function validatePasswordMatch($password, $confirm, &$error = null)
{
    if ($password !== $confirm) {
        $error = 'Passwords do not match';
        return false;
    }
    return true;
}

/**
 * Validate required fields
 */
function validateRequired($input, $fieldName = 'Field', &$error = null)
{
    if (empty($input) && $input !== '0') {
        $error = "$fieldName is required";
        return false;
    }
    return true;
}

/**
 * Validate integer
 */
function validateInt($input, &$error = null)
{
    if (filter_var($input, FILTER_VALIDATE_INT) === false) {
        $error = 'Must be a valid number';
        return false;
    }
    return true;
}

/**
 * Validate date (Y-m-d format)
 */
function validateDate($input, &$error = null)
{
    $d = DateTime::createFromFormat('Y-m-d', $input);
    if (!$d || $d->format('Y-m-d') !== $input) {
        $error = 'Invalid date format (YYYY-MM-DD)';
        return false;
    }
    return true;
}

/**
 * Validate URL
 */
function validateURL($input, &$error = null)
{
    if (!filter_var($input, FILTER_VALIDATE_URL)) {
        $error = 'Invalid URL format';
        return false;
    }
    return true;
}

/**
 * Validate phone number (basic)
 */
function validatePhone($input, &$error = null)
{
    $clean = preg_replace('/[^0-9+]/', '', $input);
    if (strlen($clean) < 10) {
        $error = 'Phone number must be at least 10 digits';
        return false;
    }
    return true;
}
