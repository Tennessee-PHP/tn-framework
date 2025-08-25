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
                    // AWS ElastiCache configuration endpoint - use as cluster seed node
                    $options['cluster'] = 'redis';

                    // Add cluster-specific options to match PHP session configuration
                    $options['parameters'] = [
                        'timeout' => 5.0,
                        'read_write_timeout' => 0,
                    ];

                    // Connect to the configuration endpoint as a cluster seed node
                    $clusterNodes = [$_ENV['REDIS_SCHEME'] . '://' . $_ENV['REDIS_HOST'] . ':' . $_ENV['REDIS_PORT']];

                    // DEBUG: Show what we're connecting to
                    var_dump([
                        'connection_type' => 'cluster_seed_node',
                        'cluster_nodes' => $clusterNodes,
                        'options' => $options,
                        'note' => 'Using seed node approach like PHP sessions'
                    ]);

                    self::$client = new Client($clusterNodes, $options);
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

                // Check if this might be a misconfigured cluster endpoint
                // by testing if the host contains cluster-like naming
                $hostname = $_ENV['REDIS_HOST'] ?? '';
                if (strpos($hostname, 'cluster') !== false || strpos($hostname, '.cache.') !== false) {
                    var_dump([
                        'warning' => 'Host appears to be a cluster endpoint but REDIS_CLUSTER=0',
                        'suggestion' => 'Try setting REDIS_CLUSTER=1 in your environment'
                    ]);
                }

                self::$client = new Client($connectionParams, $options);
            }
        }
        return self::$client;
    }
}
