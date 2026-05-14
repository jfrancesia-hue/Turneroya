<?php
// Soporta dos formatos:
//   1) DATABASE_URL (o DB_HOST con prefijo postgresql://) — connection string completa
//   2) DB_HOST/DB_PORT/DB_DATABASE/DB_USERNAME/DB_PASSWORD por separado
// Útil porque Supabase/Render/Neon dan la URL completa y pegarla en DB_HOST
// es un error común.
$url = env('DATABASE_URL');
$host = (string) env('DB_HOST', '127.0.0.1');
if (!$url && (str_starts_with($host, 'postgresql://') || str_starts_with($host, 'postgres://'))) {
    $url = $host;
}

if ($url) {
    $parts = parse_url($url);
    return [
        'connection' => env('DB_CONNECTION', 'pgsql'),
        'host'       => $parts['host'] ?? '127.0.0.1',
        'port'       => (string) ($parts['port'] ?? '5432'),
        'database'   => isset($parts['path']) ? ltrim($parts['path'], '/') : 'turneroya',
        'username'   => $parts['user'] ?? 'postgres',
        'password'   => isset($parts['pass']) ? rawurldecode($parts['pass']) : '',
    ];
}

return [
    'connection' => env('DB_CONNECTION', 'pgsql'),
    'host'       => $host,
    'port'       => env('DB_PORT', '5432'),
    'database'   => env('DB_DATABASE', 'turneroya'),
    'username'   => env('DB_USERNAME', 'postgres'),
    'password'   => env('DB_PASSWORD', ''),
];
