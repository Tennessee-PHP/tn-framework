<?php

namespace TN\TN_Core\Model\Provider\Recaptcha;

use Curl\Curl;
use TN\TN_Core\Error\ValidationException;
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
        $curl = new Curl();
        $curl->setOpt(CURLOPT_FOLLOWLOCATION, 1);
        $curl->setOpt(CURLOPT_RETURNTRANSFER, true);


        $body = [
            'secret' => $_ENV['RECAPTCHA_3_SECRET_KEY'],
            'response' => $token,
            'remoteip' => IP::getAddress()
        ];

        // trying login route
        $curl->post('https://www.google.com/recaptcha/api/siteverify', $body);
        $data = json_decode($curl->response, true);

        if (!$data['success']) {
            throw new ValidationException('Recaptcha failed');
        } else {
            return $data['score'];
        }
    }
}