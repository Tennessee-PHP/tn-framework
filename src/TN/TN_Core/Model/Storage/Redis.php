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
            // DEBUG: Output configuration being used
            var_dump([
                'REDIS_CLUSTER' => $_ENV['REDIS_CLUSTER'] ?? 'not set',
                'REDIS_HOST' => $_ENV['REDIS_HOST'] ?? 'not set',
                'REDIS_PORT' => $_ENV['REDIS_PORT'] ?? 'not set',
                'REDIS_SCHEME' => $_ENV['REDIS_SCHEME'] ?? 'not set',
                'REDIS_CLUSTER_NODES' => $_ENV['REDIS_CLUSTER_NODES'] ?? 'not set'
            ]);

            if (($_ENV['REDIS_CLUSTER'] ?? 0) == 1) {
                // Check if we have multiple cluster nodes (direct cluster access)
                // or a single configuration endpoint (AWS ElastiCache style)
                if (!empty($_ENV['REDIS_CLUSTER_NODES'])) {
                    // Multiple nodes - use true cluster mode
                    $options['cluster'] = 'redis';

                    $clusterNodes = [];
                    $nodes = explode(',', $_ENV['REDIS_CLUSTER_NODES']);
                    foreach ($nodes as $node) {
                        $node = trim($node);
                        if (!empty($node)) {
                            $clusterNodes[] = $_ENV['REDIS_SCHEME'] . '://' . $node;
                        }
                    }

                    // DEBUG: Show what we're connecting to
                    var_dump([
                        'connection_type' => 'cluster_multiple_nodes',
                        'cluster_nodes' => $clusterNodes,
                        'options' => $options
                    ]);

                    self::$client = new Client($clusterNodes, $options);
                } else {
                    // Single configuration endpoint (AWS ElastiCache) - connect as single instance
                    // The configuration endpoint handles cluster routing internally
                    $connectionParams = [
                        'scheme' => $_ENV['REDIS_SCHEME'],
                        'host' => $_ENV['REDIS_HOST'],
                        'port' => $_ENV['REDIS_PORT']
                    ];

                    // DEBUG: Show what we're connecting to
                    var_dump([
                        'connection_type' => 'cluster_config_endpoint',
                        'connection_params' => $connectionParams,
                        'options' => $options
                    ]);

                    self::$client = new Client($connectionParams, $options);
                }
            } else {
                // Single Redis instance configuration (non-cluster)
                $connectionParams = [
                    'scheme' => $_ENV['REDIS_SCHEME'],
                    'host' => $_ENV['REDIS_HOST'],
                    'port' => $_ENV['REDIS_PORT']
                ];

                // DEBUG: Show what we're connecting to
                var_dump([
                    'connection_type' => 'single',
                    'connection_params' => $connectionParams,
                    'options' => $options
                ]);

                self::$client = new Client($connectionParams, $options);
            }
        }
        return self::$client;
    }
}
