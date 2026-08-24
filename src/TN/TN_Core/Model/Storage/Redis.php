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
 *
 * Same shape as DB::getInstance($db, bool $write = false):
 * - $write false (default) → REDIS_READ_HOST, or REDIS_HOST if that is empty
 * - $write true → REDIS_HOST
 */
class Redis
{
    /** @var array{read: Client|null, write: Client|null} */
    private static array $clients = ['read' => null, 'write' => null];

    /**
     * @param bool $write if write permissions are required. Use true for SET, DEL, INCR, locks, and write-then-read.
     * @return Client get an instance of the client, instantiating it if necessary
     */
    public static function getInstance(bool $write = false): Client
    {
        $type = $write ? 'write' : 'read';
        if (self::$clients[$type] === null) {
            self::$clients[$type] = self::createClient($write);
        }
        return self::$clients[$type];
    }

    private static function createClient(bool $write): Client
    {
        $options = [
            'prefix' => $_ENV['REDIS_PREFIX']
        ];
        $host = self::host($write);

        // Use REDIS_URL if available (for Render and other cloud providers),
        // unless a read host is set for a read client.
        if (!empty($_ENV['REDIS_URL']) && ($write || empty($_ENV['REDIS_READ_HOST']))) {
            return new Client($_ENV['REDIS_URL'], $options);
        }

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

                return new Client($clusterNodes, $options);
            }

            // AWS ElastiCache cluster - use single endpoint as seed node
            $options['cluster'] = 'redis';

            // Add cluster-specific options to match PHP session configuration
            $options['parameters'] = [
                'timeout' => 5.0,
                'read_write_timeout' => 5.0,
            ];

            // Connect to the seed node, Predis will discover other cluster nodes
            $clusterNodes = [$_ENV['REDIS_SCHEME'] . '://' . $host . ':' . $_ENV['REDIS_PORT']];

            return new Client($clusterNodes, $options);
        }

        // Single Redis instance configuration (non-cluster)
        return new Client([
            'scheme' => $_ENV['REDIS_SCHEME'],
            'host' => $host,
            'port' => $_ENV['REDIS_PORT']
        ], $options);
    }

    private static function host(bool $write): string
    {
        if (!$write && !empty($_ENV['REDIS_READ_HOST'])) {
            return $_ENV['REDIS_READ_HOST'];
        }
        return $_ENV['REDIS_HOST'] ?? '';
    }

    /**
     * Cleanup Redis connections and reset singleton instances
     * Useful for error recovery and explicit connection management
     */
    public static function cleanup(): void
    {
        foreach (self::$clients as $type => $client) {
            if ($client !== null) {
                try {
                    $client->disconnect();
                } catch (\Exception $e) {
                    // Ignore disconnect errors, we're cleaning up anyway
                }
                self::$clients[$type] = null;
            }
        }
    }
}

/** register Redis cleanup method on PHP shutdown, however that occurs */
register_shutdown_function([Redis::class, 'cleanup']);
