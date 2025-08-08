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
            // Use REDIS_URL if available (for Render and other cloud providers)
            if (!empty($_ENV['REDIS_URL'])) {
                self::$client = new Client($_ENV['REDIS_URL'], $options);
            } else {
                // Fallback to individual components
                self::$client = new Client([
                    'scheme' => $_ENV['REDIS_SCHEME'],
                    'host' => $_ENV['REDIS_HOST'],
                    'port' => $_ENV['REDIS_PORT']
                ], $options);
            }
        }
        return self::$client;
    }
}
