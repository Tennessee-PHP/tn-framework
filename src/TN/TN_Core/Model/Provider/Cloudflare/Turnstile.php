<?php

namespace TN\TN_Core\Model\Provider\Cloudflare;

use Curl\Curl;
use TN\TN_Core\Model\IP\IP;

class Turnstile
{
    /**
     * @param string $token
     * @return bool
     */
    public static function verify(string $token): bool
    {
        if ($_ENV['ENV'] !== 'production') {
            return true;
        }

        $curl = new Curl();
        $curl->setOpt(CURLOPT_FOLLOWLOCATION, 1);
        $curl->setOpt(CURLOPT_RETURNTRANSFER, true);


        $body = [
            'secret' => $_ENV['CLOUDFLARE_TURNSTILE_SECRET_KEY'],
            'response' => $token,
            'remoteip' => IP::getAddress()
        ];

        $curl->post('https://challenges.cloudflare.com/turnstile/v0/siteverify', $body);
        $data = json_decode($curl->response, true);

        return (bool)($data['success'] ?? false);
    }

    /**
     * Verify token with detailed error information - throws ValidationException on failure
     * @param string $token
     * @return true
     * @throws \TN\TN_Core\Error\ValidationException
     */
    public static function verifyWithDetails(string $token): bool
    {
        if ($_ENV['ENV'] !== 'production') {
            return true;
        }

        if (empty($token)) {
            throw new \TN\TN_Core\Error\ValidationException('Token is empty');
        }

        $curl = new Curl();
        $curl->setOpt(CURLOPT_FOLLOWLOCATION, 1);
        $curl->setOpt(CURLOPT_RETURNTRANSFER, true);

        $body = [
            'secret' => $_ENV['CLOUDFLARE_TURNSTILE_SECRET_KEY'],
            'response' => $token,
            'remoteip' => IP::getAddress()
        ];

        $curl->post('https://challenges.cloudflare.com/turnstile/v0/siteverify', $body);
        $data = json_decode($curl->response, true);

        if (!is_array($data)) {
            throw new \TN\TN_Core\Error\ValidationException('Invalid response from Cloudflare');
        }

        $success = (bool)($data['success'] ?? false);

        if (!$success) {
            $errorCodes = $data['error-codes'] ?? [];
            $errorMessages = [
                'missing-input-secret' => 'The secret parameter was not passed',
                'invalid-input-secret' => 'The secret parameter was invalid or did not exist',
                'missing-input-response' => 'The response parameter (token) was not passed',
                'invalid-input-response' => 'The response parameter (token) is invalid or has expired',
                'bad-request' => 'The request was rejected because it was malformed',
                'timeout-or-duplicate' => 'The response parameter (token) has already been validated before or has expired',
                'internal-error' => 'An internal error happened while validating the response'
            ];

            $messages = [];
            foreach ($errorCodes as $code) {
                $messages[] = $errorMessages[$code] ?? "Unknown error code: $code";
            }

            $message = empty($messages) ? 'Unknown verification error' : implode(', ', $messages);
            $tokenPreview = substr($token, 0, 20) . '...';

            throw new \TN\TN_Core\Error\ValidationException($message . ' (token: ' . $tokenPreview . ')');
        }

        return true;
    }
}
