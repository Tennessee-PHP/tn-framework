<?php

namespace TN\TN_Core\Model\Provider\Recaptcha;

use TN\TN_Core\Error\ValidationException;
use TN\TN_Core\Model\HTTP\OutboundHttp;
use TN\TN_Core\Model\IP\IP;

class Recaptcha
{
    /**
     * @param string $token
     * @return float
     * @throws ValidationException
     */
    public static function getScore(string $token): float
    {
        $body = [
            'secret' => $_ENV['RECAPTCHA_3_SECRET_KEY'],
            'response' => $token,
            'remoteip' => IP::getAddress()
        ];

        $response = OutboundHttp::post('https://www.google.com/recaptcha/api/siteverify', $body);
        $data = json_decode($response ?? '', true);

        if (!is_array($data) || !($data['success'] ?? false)) {
            throw new ValidationException('Recaptcha failed');
        }

        return (float) $data['score'];
    }
}
