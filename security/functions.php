<?php

define('TRUSTED_PROXIES', []);
define('PROXY_HEADERS', ['HTTP_X_FORWARDED_FOR', 'HTTP_CF_CONNECTING_IP']);

function secure_session_regenerate()
{
    session_regenerate_id(true);
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), session_id(), 0, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
    }
}
function getCSRFToken()
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function validateCSRFToken($token)
{
    if (!isset($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $token)) {
        http_response_code(403);
        die('Invalid CSRF token.');
    }
}
function regenerateCSRFToken()
{
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    return $_SESSION['csrf_token'];
}

function encodeID($id)
{
    global $hashids;
    return $hashids->encode($id);
}

function decodeID($hash)
{
    global $hashids;
    $decoded = $hashids->decode($hash);
    return $decoded ? $decoded[0] : null;
}

function getRealIP()
{
    $clientIP = $_SERVER['REMOTE_ADDR'];

    // Only trust proxy headers if request comes FROM a trusted proxy
    if (!in_array($clientIP, TRUSTED_PROXIES, true)) {
        return filter_var($clientIP, FILTER_VALIDATE_IP) ? $clientIP : '0.0.0.0';
    }

    // Check each trusted header in order
    foreach (PROXY_HEADERS as $header) {
        if (!empty($_SERVER[$header])) {
            // Get first IP from comma-separated list
            $ip = trim(explode(',', $_SERVER[$header])[0]);
            if (filter_var($ip, FILTER_VALIDATE_IP)) {
                return $ip;
            }
        }
    }

    return '0.0.0.0';
}