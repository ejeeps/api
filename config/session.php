<?php
/**
 * Shared session bootstrap so the session cookie path matches the app root
 * (e.g. /api/) for every entry point. Without this, sessions started from
 * controller/auth/*.php can set a cookie scoped to /api/controller/auth/,
 * and index.php never receives it — login appears to "fail" after redirect.
 */
if (!function_exists('app_path_prefix')) {
    /**
     * URL path segment(s) from document root to app root, no leading/trailing slashes.
     * Empty string when the app lives at site root (e.g. index.php at /index.php).
     */
    function app_path_prefix(): string
    {
        $scriptName = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '');
        $dir = dirname($scriptName);
        if (preg_match('#/controller/auth/[^/]+\.php$#i', $scriptName)) {
            $dir = dirname(dirname($dir));
        }
        if ($dir === '/' || $dir === '\\' || $dir === '.') {
            return '';
        }
        return trim($dir, '/');
    }
}

if (!function_exists('app_session_cookie_path')) {
    function app_session_cookie_path(): string
    {
        $prefix = app_path_prefix();
        return $prefix === '' ? '/' : '/' . $prefix . '/';
    }
}

if (!function_exists('app_index_url')) {
    /** Absolute URL to index.php (works with subfolders and reverse proxies). */
    function app_index_url(array $query = []): string
    {
        $https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
            || (isset($_SERVER['SERVER_PORT']) && (string) $_SERVER['SERVER_PORT'] === '443');
        $scheme = $https ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'] ?? $_SERVER['SERVER_NAME'] ?? 'localhost';
        $prefix = app_path_prefix();
        $path = ($prefix === '' ? '' : '/' . $prefix) . '/index.php';
        $url = $scheme . '://' . $host . $path;
        if ($query !== []) {
            $url .= '?' . http_build_query($query);
        }
        return $url;
    }
}

if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => app_session_cookie_path(),
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}
