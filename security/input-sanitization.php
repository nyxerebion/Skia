<?php
function sanitizeString($input)
{
    return trim(strip_tags($input));
}

/**
 * Sanitize email
 */
function sanitizeEmail($input)
{
    $email = filter_var(trim($input), FILTER_SANITIZE_EMAIL);
    return filter_var($email, FILTER_VALIDATE_EMAIL) ? $email : '';
}

/**
 * Sanitize integer
 */
function sanitizeInt($input)
{
    $filtered = filter_var($input, FILTER_VALIDATE_INT);
    return $filtered !== false ? (int)$filtered : null;
}

/**
 * Sanitize URL
 */
function sanitizeURL($input)
{
    $url = filter_var(trim($input), FILTER_SANITIZE_URL);
    return filter_var($url, FILTER_VALIDATE_URL) ? $url : '';
}

/**
 * Sanitize filename (remove path traversal)
 */
function sanitizeFilename($input)
{
    // Remove any path separators
    $clean = preg_replace('/[\/\\\\]+/', '', $input);
    // Allow only alphanumeric, dot, dash, underscore
    return preg_replace('/[^a-zA-Z0-9.\-_]/', '', $clean);
}

/**
 * Sanitize entire array (recursive)
 */
function sanitizeArray($array)
{
    foreach ($array as $key => $value) {
        if (is_array($value)) {
            $array[$key] = sanitizeArray($value);
        } else {
            $array[$key] = sanitizeString($value);
        }
    }
    return $array;
}

/**
 * Sanitize and validate username
 */
function sanitizeUsername($input)
{
    $clean = preg_replace('/[^a-zA-Z0-9_]/', '', trim($input));
    return substr($clean, 0, 12); // Max length 12
}

/**
 * Sanitize textarea (preserve line breaks, remove dangerous tags)
 */
function sanitizeTextarea($input, $max = 1000)
{
    $clean = strip_tags($input);
    return substr(trim($clean), 0, $max);
}
