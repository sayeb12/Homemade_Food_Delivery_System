<?php
function app_env($key, $default = null) {
    static $fileEnv = null;

    if ($fileEnv === null) {
        $envPath = dirname(__DIR__) . '/.env';
        $fileEnv = is_readable($envPath) ? parse_ini_file($envPath, false, INI_SCANNER_RAW) : [];
    }

    $value = getenv($key);
    if ($value !== false && $value !== '') {
        return $value;
    }

    return $fileEnv[$key] ?? $default;
}
?>
