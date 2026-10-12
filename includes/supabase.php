<?php
declare(strict_types=1);

/**
 * Minimal Supabase (PostgREST) client. Runs only on the server with the key
 * from includes/config.local.php; nothing here is ever sent to the browser.
 */

require_once __DIR__ . '/config.php';

/** True when the Supabase credentials are configured. */
function supabase_configured(): bool
{
    return defined('SUPABASE_URL') && defined('SUPABASE_KEY') && SUPABASE_URL !== '' && SUPABASE_KEY !== '';
}

/**
 * Sends a request to the PostgREST API.
 *
 * @param string $path e.g. "sales?select=id&status=eq.SIGNED"
 * @return array{status: int, data: mixed, headers: string}
 */
function supabase_request(string $method, string $path, ?array $body = null, array $extraHeaders = []): array
{
    if (!supabase_configured()) {
        return ['status' => 0, 'data' => ['error' => 'Supabase is not configured'], 'headers' => ''];
    }

    $ch = curl_init(rtrim(SUPABASE_URL, '/') . '/rest/v1/' . ltrim($path, '/'));
    curl_setopt_array($ch, [
        CURLOPT_CUSTOMREQUEST => $method,
        CURLOPT_HTTPHEADER => array_merge([
            'apikey: ' . SUPABASE_KEY,
            'Authorization: Bearer ' . SUPABASE_KEY,
            'Content-Type: application/json',
            'Accept: application/json',
        ], $extraHeaders),
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HEADER => true,
        CURLOPT_CONNECTTIMEOUT => 5,
        CURLOPT_TIMEOUT => 15,
    ]);
    if ($body !== null) {
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($body));
    }

    $response = curl_exec($ch);
    if ($response === false) {
        $error = curl_error($ch);
        curl_close($ch);
        return ['status' => 0, 'data' => ['error' => $error], 'headers' => ''];
    }

    $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $headerSize = (int) curl_getinfo($ch, CURLINFO_HEADER_SIZE);
    curl_close($ch);

    return [
        'status' => $status,
        'data' => json_decode(substr($response, $headerSize), true),
        'headers' => substr($response, 0, $headerSize),
    ];
}

/** Rows from a table, or [] on any error. */
function supabase_select(string $table, string $query = ''): array
{
    $res = supabase_request('GET', $table . ($query !== '' ? '?' . $query : ''));
    return $res['status'] === 200 && is_array($res['data']) ? $res['data'] : [];
}

/** Counts rows without downloading them (Prefer: count=exact). */
function supabase_count(string $table, string $query = ''): int
{
    $path = $table . '?' . ($query !== '' ? $query . '&' : '') . 'select=id&limit=1';
    $res = supabase_request('GET', $path, null, ['Prefer: count=exact']);
    if (preg_match('#content-range:\s*[\d*-]+/(\d+)#i', $res['headers'], $m)) {
        return (int) $m[1];
    }
    return 0;
}

/**
 * Runs several SELECTs at the same time (HTTP/2 multiplexed over one
 * connection), with an optional short server-side cache.
 *
 * @param array<string, string> $paths key => "table?query"
 * @param int $ttl cache lifetime in seconds (0 = no cache)
 * @return array<string, array> key => rows ([] on error)
 */
function supabase_select_many(array $paths, int $ttl = 0): array
{
    $results = [];
    $pending = [];
    foreach ($paths as $key => $path) {
        $cached = $ttl > 0 ? cache_get($path, $ttl) : null;
        $cached !== null ? $results[$key] = $cached : $pending[$key] = $path;
    }
    if ($pending === [] || !supabase_configured()) {
        return $results + array_fill_keys(array_keys($pending), []);
    }

    $multi = curl_multi_init();
    curl_multi_setopt($multi, CURLMOPT_PIPELINING, CURLPIPE_MULTIPLEX);
    $handles = [];
    foreach ($pending as $key => $path) {
        $ch = curl_init(rtrim(SUPABASE_URL, '/') . '/rest/v1/' . ltrim($path, '/'));
        curl_setopt_array($ch, [
            CURLOPT_HTTPHEADER => ['apikey: ' . SUPABASE_KEY, 'Authorization: Bearer ' . SUPABASE_KEY, 'Accept: application/json'],
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_2TLS,
            CURLOPT_PIPEWAIT => true,
            CURLOPT_CONNECTTIMEOUT => 5,
            CURLOPT_TIMEOUT => 15,
        ]);
        curl_multi_add_handle($multi, $ch);
        $handles[$key] = $ch;
    }

    do {
        $status = curl_multi_exec($multi, $running);
        if ($running) {
            curl_multi_select($multi);
        }
    } while ($running && $status === CURLM_OK);

    foreach ($handles as $key => $ch) {
        $data = json_decode((string) curl_multi_getcontent($ch), true);
        $ok = curl_getinfo($ch, CURLINFO_HTTP_CODE) === 200 && is_array($data);
        $results[$key] = $ok ? $data : [];
        if ($ok && $ttl > 0) {
            cache_set($pending[$key], $data);
        }
        curl_multi_remove_handle($multi, $ch);
        curl_close($ch);
    }
    curl_multi_close($multi);
    return $results;
}

/* ---------- Short-lived server cache (storage/cache, git-ignored, never served) ---------- */

function cache_file(string $key): string
{
    return dirname(__DIR__) . '/storage/cache/' . hash('sha256', $key) . '.json';
}

function cache_get(string $key, int $ttl): ?array
{
    $file = cache_file($key);
    if (!is_file($file) || filemtime($file) < time() - $ttl) {
        return null;
    }
    $data = json_decode((string) file_get_contents($file), true);
    return is_array($data) ? $data : null;
}

function cache_set(string $key, array $data): void
{
    $dir = dirname(cache_file($key));
    if (!is_dir($dir) && !@mkdir($dir, 0700, true)) {
        return;
    }
    $tmp = cache_file($key) . '.' . bin2hex(random_bytes(4));
    if (@file_put_contents($tmp, json_encode($data), LOCK_EX) !== false) {
        @chmod($tmp, 0600);
        @rename($tmp, cache_file($key));
    }
}

