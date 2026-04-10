<?php

namespace TN\TN_Core\Model\Provider\ConvertKit;

/**
 * Synchronous POST to Kit v3 {@code POST /v3/forms/:id/subscribe}.
 */
class KitV3FormSubscribe
{
    /**
     * @param array<string, string> $fields Custom field key/value pairs
     * @param list<int> $tagIds Numeric tag ids (resolved from names before calling)
     */
    public static function subscribe(int $formId, string $email, array $fields, array $tagIds): bool
    {
        $apiKey = (string) ($_ENV['CONVERTKIT_KEY'] ?? '');
        if ($apiKey === '') {
            error_log('KitV3FormSubscribe: CONVERTKIT_KEY missing');
            return false;
        }

        $payload = [
            'api_key' => $apiKey,
            'email' => $email,
        ];

        if ($fields !== []) {
            $payload['fields'] = $fields;
        }

        if (isset($fields['first_name']) && $fields['first_name'] !== '') {
            $payload['first_name'] = $fields['first_name'];
        }

        if ($tagIds !== []) {
            $payload['tags'] = array_values($tagIds);
        }

        $url = 'https://api.convertkit.com/v3/forms/' . $formId . '/subscribe';
        $body = json_encode($payload);
        if ($body === false) {
            return false;
        }

        $ch = curl_init($url);
        if ($ch === false) {
            return false;
        }
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $body,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 20,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json; charset=utf-8',
                'Accept: application/json',
            ],
        ]);
        $raw = curl_exec($ch);
        $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $err = curl_error($ch);
        curl_close($ch);

        if ($raw === false || $err !== '') {
            error_log('KitV3FormSubscribe: curl error ' . $err);
            return false;
        }

        if ($code < 200 || $code >= 300) {
            error_log('KitV3FormSubscribe: HTTP ' . $code . ' body=' . substr((string) $raw, 0, 500));
            return false;
        }

        return true;
    }
}
