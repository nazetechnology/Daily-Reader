<?php
// Hostinger-compatible daily.dev API proxy for The Daily Reader.
// Keep this file on the same HTTPS domain as index.html.

declare(strict_types=1);

const DAILY_HOST = 'api.daily.dev';
const DAILY_BASE = '/public/v1';

function send_json(int $status, $data): never
{
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: no-store');
    echo json_encode($data, JSON_UNESCAPED_SLASHES);
    exit;
}

function request_header(string $name): string
{
    $key = 'HTTP_' . strtoupper(str_replace('-', '_', $name));
    return trim((string) ($_SERVER[$key] ?? ''));
}

function request_path(): string
{
    $uri = (string) ($_SERVER['REQUEST_URI'] ?? '/proxy.php');
    $path = (string) (parse_url($uri, PHP_URL_PATH) ?? '/proxy.php');
    $script = (string) ($_SERVER['SCRIPT_NAME'] ?? '/proxy.php');

    if (str_starts_with($path, $script)) {
        $path = substr($path, strlen($script));
    }

    return $path === '' ? '/' : $path;
}

function translate_path(string $path): string
{
    if ($path !== '/posts' && $path !== '/posts/') {
        return $path;
    }

    $page_size = filter_input(INPUT_GET, 'pageSize', FILTER_VALIDATE_INT);
    $limit = max(1, min(50, $page_size ?: 20));
    $tag = trim((string) ($_GET['tag'] ?? ''));

    if ($tag !== '') {
        return '/feeds/tag/' . rawurlencode($tag) . '?limit=' . $limit;
    }

    return '/feeds/foryou?limit=' . $limit;
}

function api_token(): string
{
    $server_token = trim((string) getenv('DAILY_KEY'));
    if ($server_token !== '') {
        return $server_token;
    }

    $authorization = request_header('Authorization');
    if (preg_match('/^Bearer\s+(.+)$/i', $authorization, $matches)) {
        return trim($matches[1]);
    }

    return request_header('api-key');
}

function transform_response($json)
{
    if (is_array($json) && isset($json['data']) && is_array($json['data']) && isset($json['pagination'])) {
        return [
            'posts' => $json['data'],
            'paginationInfo' => [
                'hasNext' => ($json['pagination']['hasNextPage'] ?? false) === true,
                'cursor' => $json['pagination']['cursor'] ?? null,
            ],
            'data' => $json['data'],
            'pagination' => $json['pagination'],
        ];
    }

    return $json;
}

$origin = trim((string) ($_SERVER['HTTP_ORIGIN'] ?? ''));
header('Access-Control-Allow-Origin: ' . ($origin !== '' ? $origin : '*'));
header('Vary: Origin');
header('Access-Control-Allow-Headers: Authorization, api-key, Content-Type');
header('Access-Control-Allow-Methods: GET, OPTIONS');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    send_json(405, [
        'error' => 'method_not_allowed',
        'message' => 'Only GET requests are supported.',
    ]);
}

$target_path = translate_path(request_path());
$target_url = 'https://' . DAILY_HOST . DAILY_BASE . $target_path;
$token = api_token();

$headers = [
    'Accept: application/json',
    'User-Agent: Daily-Reader-Proxy/1.0',
];
if ($token !== '') {
    $headers[] = 'Authorization: Bearer ' . $token;
}

$curl = curl_init($target_url);
if ($curl === false) {
    send_json(500, ['error' => 'proxy_error', 'message' => 'Could not initialize the upstream request.']);
}

curl_setopt_array($curl, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_FOLLOWLOCATION => false,
    CURLOPT_HTTPHEADER => $headers,
    CURLOPT_CONNECTTIMEOUT => 10,
    CURLOPT_TIMEOUT => 30,
]);

$body = curl_exec($curl);
$curl_error = curl_error($curl);
$status = (int) curl_getinfo($curl, CURLINFO_HTTP_CODE);
curl_close($curl);

if ($body === false) {
    send_json(502, ['error' => 'upstream_failed', 'message' => $curl_error ?: 'The upstream request failed.']);
}

$json = json_decode($body, true);
if (json_last_error() !== JSON_ERROR_NONE) {
    send_json($status ?: 502, [
        'error' => 'invalid_upstream_response',
        'message' => 'daily.dev returned a non-JSON response.',
        'upstreamStatus' => $status,
    ]);
}

send_json($status ?: 502, ($status >= 200 && $status < 300) ? transform_response($json) : $json);
