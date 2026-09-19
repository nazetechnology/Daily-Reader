<?php
declare(strict_types=1);

const DAILY_HOST = 'api.daily.dev';
const DAILY_BASE = '/public/v1';
const SEARCH_TIMES = ['day' => true, 'week' => true, 'month' => true, 'year' => true, 'all' => true];

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

function bounded_int($value, int $fallback, int $min, int $max): int
{
    $n = filter_var($value, FILTER_VALIDATE_INT);
    if ($n === false || $n === null) {
        return $fallback;
    }

    return max($min, min($max, (int) $n));
}

function build_search_path(): string
{
    $query = trim((string) ($_GET['q'] ?? $_GET['query'] ?? $_GET['search'] ?? ''));
    if ($query === '') {
        return '';
    }

    $mode = ($_GET['mode'] ?? '') === 'semantic' ? 'semantic' : 'keyword';
    $params = [
        'q' => $query,
        'limit' => (string) bounded_int($_GET['limit'] ?? $_GET['pageSize'] ?? null, 10, 1, 20),
    ];

    $time = (string) ($_GET['time'] ?? '');
    if (isset(SEARCH_TIMES[$time])) {
        $params['time'] = $time;
    }

    $cursor = trim((string) ($_GET['cursor'] ?? ''));
    if ($mode === 'keyword' && $cursor !== '') {
        $params['cursor'] = $cursor;
    }

    return '/recommend/' . $mode . '?' . http_build_query($params, '', '&', PHP_QUERY_RFC3986);
}

function translate_path(string $path): string
{
    if ($path === '/search' || $path === '/search/') {
        $search_path = build_search_path();
        $query_string = (string) ($_SERVER['QUERY_STRING'] ?? '');
        return $search_path !== '' ? $search_path : '/recommend/keyword' . ($query_string !== '' ? '?' . $query_string : '');
    }

    if ($path !== '/posts' && $path !== '/posts/') {
        return $path;
    }

    $search_path = build_search_path();
    if ($search_path !== '') {
        return $search_path;
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

function transform_response($json, string $path)
{
    $list_endpoint = str_starts_with($path, '/feeds/') || str_starts_with($path, '/recommend/');

    if ($list_endpoint && is_array($json) && isset($json['data']) && is_array($json['data']) && (isset($json['pagination']) || str_starts_with($path, '/recommend/'))) {
        $pagination = is_array($json['pagination'] ?? null) ? $json['pagination'] : [];

        return [
            'posts' => $json['data'],
            'paginationInfo' => [
                'hasNext' => ($pagination['hasNextPage'] ?? false) === true,
                'cursor' => $pagination['cursor'] ?? null,
            ],
            'data' => $json['data'],
            'pagination' => $pagination,
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

send_json($status ?: 502, ($status >= 200 && $status < 300) ? transform_response($json, $target_path) : $json);
