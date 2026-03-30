<?php
/**
 * Groq API key — https://console.groq.com/keys
 *
 * Resolution order:
 * 1) GROQ_API_KEY in project root .env (same folder as index.php)
 * 2) config/groq.local.php — use define('GROQ_API_KEY', 'gsk_...'); (recommended on XAMPP; file is gitignored)
 * 3) getenv('GROQ_API_KEY') / $_ENV
 */
if (!function_exists('ejeep_load_groq_env_file')) {
    function ejeep_load_groq_env_file(): void
    {
        static $done = false;
        if ($done) {
            return;
        }
        $done = true;
        $path = dirname(__DIR__) . DIRECTORY_SEPARATOR . '.env';
        if (!is_readable($path)) {
            return;
        }
        $lines = @file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        if (!is_array($lines)) {
            return;
        }
        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '' || (isset($line[0]) && $line[0] === '#')) {
                continue;
            }
            if (!preg_match('/^GROQ_API_KEY\s*=\s*(.*)$/', $line, $m)) {
                continue;
            }
            $val = trim($m[1]);
            $val = trim($val, "\"'");
            if ($val !== '') {
                putenv('GROQ_API_KEY=' . $val);
                $_ENV['GROQ_API_KEY'] = $val;
            }
        }
    }
}

ejeep_load_groq_env_file();

if (file_exists(__DIR__ . DIRECTORY_SEPARATOR . 'groq.local.php')) {
    require_once __DIR__ . DIRECTORY_SEPARATOR . 'groq.local.php';
}

function ejeep_groq_api_key(): string
{
    if (defined('GROQ_API_KEY')) {
        $k = constant('GROQ_API_KEY');
        if (is_string($k) && $k !== '') {
            return $k;
        }
    }
    $v = getenv('GROQ_API_KEY');
    if (is_string($v) && $v !== '') {
        return $v;
    }
    if (isset($_ENV['GROQ_API_KEY']) && is_string($_ENV['GROQ_API_KEY']) && $_ENV['GROQ_API_KEY'] !== '') {
        return $_ENV['GROQ_API_KEY'];
    }
    return '';
}
