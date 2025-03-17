<?php

namespace TN\TN_Core\Model\Storage;

use Predis\Client;

/**
 * Class implementing singleton redis client
 *
 */
class Redis
{
    /** @var Client local instance of the caching client */
    private static Client $client;

    /** @return Client get an instance of the client, instantiating it if necessary */
    public static function getInstance()
    {
        $options = [
            'prefix' => $_ENV['REDIS_PREFIX']
        ];

        if (($_ENV['REDIS_CLUSTER'] ?? 0) == 1) {
            $options['cluster'] = 'redis';
        }

        if (!isset(self::$client)) {
            self::$client = new Client([
                'scheme' => $_ENV['REDIS_SCHEME'],
                'host' => $_ENV['REDIS_HOST'],
                'port' => $_ENV['REDIS_PORT']
            ], $options);
        }
        return self::$client;
    }

}

?>