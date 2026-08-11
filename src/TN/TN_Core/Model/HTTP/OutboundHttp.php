<?php

namespace TN\TN_Core\Model\HTTP;

use TN\TN_Core\Model\Storage\Cache;
use TN\TN_Core\Model\Time\Time;

/**
 * Shared outbound HTTP client with standard timeouts and optional Redis fresh/stale caching.
 *
 * Never hangs PHP workers on a stalled upstream: every request uses connect + total timeouts.
 * When cacheKey/freshTtl/staleTtl are set, serve fresh cache without a network call; on transport
 * or HTTP failure, serve last good body until staleTtl expires.
 */
class OutboundHttp
{
    public const string PROFILE_DEFAULT = 'default';
    public const string PROFILE_CLOUD = 'cloud';

    public const int DEFAULT_CONNECT_TIMEOUT = 2;
    public const int DEFAULT_TIMEOUT = 5;
    public const int CLOUD_CONNECT_TIMEOUT = 5;
    public const int CLOUD_TIMEOUT = Time::ONE_MINUTE;

    public const int MAX_REDIRECTS = 5;

    /**
     * @param array{
     *     headers?: array<string, string>|list<string>,
     *     timeoutProfile?: string,
     *     cacheKey?: string,
     *     freshTtl?: int,
     *     staleTtl?: int,
     *     userAgent?: string
     * } $options
     */
    public static function get(string $url, array $options = []): ?string
    {
        return self::request('GET', $url, null, $options);
    }

    /**
     * @param array<string, mixed>|string $body
     * @param array{
     *     headers?: array<string, string>|list<string>,
     *     timeoutProfile?: string,
     *     cacheKey?: string,
     *     freshTtl?: int,
     *     staleTtl?: int,
     *     userAgent?: string
     * } $options
     */
    public static function post(string $url, array|string $body, array $options = []): ?string
    {
        return self::request('POST', $url, $body, $options);
    }

    /**
     * Apply standard timeout options to a php-curl-class Curl instance.
     */
    public static function applyTimeouts(\Curl\Curl $curl, string $profile = self::PROFILE_DEFAULT): void
    {
        [$connect, $total] = self::timeoutsForProfile($profile);
        $curl->setOpt(CURLOPT_CONNECTTIMEOUT, $connect);
        $curl->setOpt(CURLOPT_TIMEOUT, $total);
    }

    /**
     * @param array<string, mixed>|string|null $body
     * @param array<string, mixed> $options
     */
    private static function request(string $method, string $url, array|string|null $body, array $options): ?string
    {
        $cacheKey = $options['cacheKey'] ?? null;
        $freshTtl = (int) ($options['freshTtl'] ?? 0);
        $staleTtl = (int) ($options['staleTtl'] ?? 0);
        $useCache = is_string($cacheKey) && $cacheKey !== '' && $freshTtl > 0 && $staleTtl > 0;

        $cached = null;
        if ($useCache) {
            $cached = self::readCache($cacheKey);
            if ($cached !== null && (Time::getNow() - $cached['fetchedAt']) < $freshTtl) {
                return $cached['body'];
            }
        }

        $response = self::execute($method, $url, $body, $options);
        if ($response !== null) {
            if ($useCache) {
                self::writeCache($cacheKey, $response, $staleTtl);
            }
            return $response;
        }

        if ($useCache && $cached !== null) {
            return $cached['body'];
        }

        return null;
    }

    /**
     * @param array<string, mixed>|string|null $body
     * @param array<string, mixed> $options
     */
    private static function execute(string $method, string $url, array|string|null $body, array $options): ?string
    {
        $profile = (string) ($options['timeoutProfile'] ?? self::PROFILE_DEFAULT);
        [$connect, $total] = self::timeoutsForProfile($profile);

        $ch = curl_init($url);
        if ($ch === false) {
            return null;
        }

        $headers = self::normalizeHeaders($options['headers'] ?? []);

        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS => self::MAX_REDIRECTS,
            CURLOPT_CONNECTTIMEOUT => $connect,
            CURLOPT_TIMEOUT => $total,
            CURLOPT_HTTPHEADER => $headers,
        ]);

        if (!empty($options['userAgent']) && is_string($options['userAgent'])) {
            curl_setopt($ch, CURLOPT_USERAGENT, $options['userAgent']);
        }

        if (strtoupper($method) === 'POST') {
            curl_setopt($ch, CURLOPT_POST, true);
            if (is_array($body)) {
                curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($body));
            } else {
                curl_setopt($ch, CURLOPT_POSTFIELDS, (string) ($body ?? ''));
            }
        }

        $raw = curl_exec($ch);
        $errno = curl_errno($ch);
        $httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($errno !== 0 || $raw === false || !is_string($raw)) {
            return null;
        }

        if ($httpCode < 200 || $httpCode >= 400) {
            return null;
        }

        return $raw;
    }

    /**
     * @return array{0: int, 1: int} connect, total
     */
    private static function timeoutsForProfile(string $profile): array
    {
        if ($profile === self::PROFILE_CLOUD) {
            return [self::CLOUD_CONNECT_TIMEOUT, self::CLOUD_TIMEOUT];
        }
        return [self::DEFAULT_CONNECT_TIMEOUT, self::DEFAULT_TIMEOUT];
    }

    /**
     * @param array<string, string>|list<string> $headers
     * @return list<string>
     */
    private static function normalizeHeaders(array $headers): array
    {
        $normalized = [];
        foreach ($headers as $key => $value) {
            if (is_int($key)) {
                $normalized[] = (string) $value;
            } else {
                $normalized[] = $key . ': ' . $value;
            }
        }
        return $normalized;
    }

    /**
     * @return array{body: string, fetchedAt: int}|null
     */
    private static function readCache(string $cacheKey): ?array
    {
        $data = Cache::get($cacheKey);
        if (!is_array($data) || !isset($data['body'], $data['fetchedAt'])) {
            return null;
        }
        if (!is_string($data['body']) || !is_int($data['fetchedAt'])) {
            return null;
        }
        return $data;
    }

    private static function writeCache(string $cacheKey, string $body, int $staleTtl): void
    {
        Cache::set($cacheKey, [
            'body' => $body,
            'fetchedAt' => Time::getNow(),
        ], $staleTtl);
    }
}
