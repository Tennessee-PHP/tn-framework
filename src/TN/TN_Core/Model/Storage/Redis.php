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

        if (!isset(self::$client)) {
            if (($_ENV['REDIS_CLUSTER'] ?? 0) == 1) {
                // Redis Cluster configuration
                $options['cluster'] = 'redis';

                // Parse cluster nodes from environment variable
                $clusterNodes = [];
                if (!empty($_ENV['REDIS_CLUSTER_NODES'])) {
                    // Format: "host1:port1,host2:port2,host3:port3"
                    $nodes = explode(',', $_ENV['REDIS_CLUSTER_NODES']);
                    foreach ($nodes as $node) {
                        $node = trim($node);
                        if (!empty($node)) {
                            $clusterNodes[] = $_ENV['REDIS_SCHEME'] . '://' . $node;
                        }
                    }
                } else {
                    // Single node cluster proxy - connect to one endpoint that handles cluster routing
                    $clusterNodes[] = $_ENV['REDIS_SCHEME'] . '://' . $_ENV['REDIS_HOST'] . ':' . $_ENV['REDIS_PORT'];
                }

                self::$client = new Client($clusterNodes, $options);
            } else {
                // Single Redis instance configuration (non-cluster)
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
