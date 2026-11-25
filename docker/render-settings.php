<?php
// Membuat hesk_settings.inc.php dari hesk_settings.inc.php.example + environment variable.
$root = getenv('APP_ROOT') ?: '/var/www/html';
$src  = $root . '/hesk_settings.inc.php.example';
$dst  = $root . '/hesk_settings.inc.php';

if (!is_file($src)) {
    fwrite(STDERR, "[laporti] hesk_settings.inc.php.example tidak ditemukan di $root\n");
    exit(1);
}

$env = function ($key, $default = '') {
    $v = getenv($key);
    return ($v === false || $v === '') ? $default : $v;
};

$map = [
    '__SITE_URL__'  => $env('SITE_URL', 'http://localhost:8080'),
    '__DB_HOST__'   => $env('DB_HOST', 'db'),
    '__DB_NAME__'   => $env('DB_NAME', 'laporti'),
    '__DB_USER__'   => $env('DB_USER', 'laporti'),
    '__DB_PASS__'   => $env('DB_PASS', ''),
    '__DB_PREFIX__' => $env('DB_PREFIX', 'b2ca_'),
    '__URL_KEY__'   => substr(bin2hex(random_bytes(16)), 0, 30),
];

foreach ($map as $k => $v) {
    $map[$k] = addcslashes($v, "'\\");
}

file_put_contents($dst, strtr(file_get_contents($src), $map));
chmod($dst, 0666);
echo "[laporti] hesk_settings.inc.php dibuat dari example\n";
