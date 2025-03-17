<?php

namespace TN\TN_Core\Model\Provider\Cloudflare;

use Curl\Curl;
use TN\TN_Core\Model\IP\IP;

class Turnstile {
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
}