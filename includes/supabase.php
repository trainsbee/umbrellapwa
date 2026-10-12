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
