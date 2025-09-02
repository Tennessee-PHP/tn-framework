<?php

namespace TN\TN_Core\Model\Storage;

use Predis\Client;

/**
 * Redis client singleton with support for both single instances and clusters
 * 
 * Supports:
 * - Single Redis instances (REDIS_CLUSTER=0)
 * - AWS ElastiCache clusters via seed node (REDIS_CLUSTER=1, no REDIS_CLUSTER_NODES)  
 * - Direct cluster access with multiple nodes (REDIS_CLUSTER=1, with REDIS_CLUSTER_NODES)
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
                // Check if we have multiple cluster nodes (direct cluster access)
                // or a single seed node (AWS ElastiCache style)
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

                    self::$client = new Client($clusterNodes, $options);
                } else {
                    // AWS ElastiCache cluster - use single endpoint as seed node
                    $options['cluster'] = 'redis';

                    // Add cluster-specific options to match PHP session configuration
                    $options['parameters'] = [
                        'timeout' => 5.0,
                        'read_write_timeout' => 5.0,
                    ];

                    // Connect to the seed node, Predis will discover other cluster nodes
                    $clusterNodes = [$_ENV['REDIS_SCHEME'] . '://' . $_ENV['REDIS_HOST'] . ':' . $_ENV['REDIS_PORT']];

                    self::$client = new Client($clusterNodes, $options);
                }
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

    /**
     * Cleanup Redis connection and reset singleton instance
     * Useful for error recovery and explicit connection management
     */
    public static function cleanup(): void
    {
        if (isset(self::$client)) {
            try {
                self::$client->disconnect();
            } catch (\Exception $e) {
                // Ignore disconnect errors, we're cleaning up anyway
            }
            unset(self::$client);
        }
    }
}

/** register Redis cleanup method on PHP shutdown, however that occurs */
register_shutdown_function([Redis::class, 'cleanup']);
