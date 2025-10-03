<?php

namespace TN\TN_Core\Model\Storage;

use JetBrains\PhpStorm\ArrayShape;
use TN\TN_Core\Model\Package\Stack;
use function JmesPath\search;

/**
 * Class implementing singleton PDO connections and consuming TN-defined DB connection environmental variables
 * 
 *
 *
 * @example
 * - Connect to the Database
 *
 * Old way:
 * <code>
 * $connection = mysqli_connect("location", "user", "pass");
 * </code>
 *
 * New way: (**nb: database name must be specified**)
 * <code>
 * $db = DB::getInstance('database_name');
 * </code>
 *
 * @example
 * - Query the Database
 *
 * Old way:
 * <code>
 * $query = "select * from table where col=$colValue";
 * $result = mysqli_query($connection, $query);
 * </code>
 *
 * New way:
 * <code>
 * $stmt = $db->prepare("SELECT * FROM table WHERE col = ?");
 * $stmt->execute([$colValue]);
 * </code>
 *
 * @example
 * - Fetch Query Results as Associative Array
 *
 * Old way:
 * <code>
 * while ($row = mysqli_fetch_assoc($result)) { ... }
 * </code>
 *
 * New way:
 * <code>
 * while ($row = $stmt->fetch(DB::FETCH_ASSOC)) { ... }
 * </code>
 *
 * @example
 * - Fetch Query Results as Numbered Array
 *
 * __Do not use this method with SELECT *__ as changing column order on a database table may break queries
 *
 * Old way:
 * <code>
 * while (list($col1, $col2) = mysqli_fetch_row($result)) { ... }
 * </code>
 *
 * New way:
 * <code>
 * while (list($col1, $col2) = $stmt->fetch(DB::FETCH_NUM)) { ... }
 * </code>
 *
 * @link https://www.php.net/manual/en/class.pdo The official PHP PDO Documentation
 * @link https://phpdelusions.net/pdo This is a fantastic article outlining PDO best practice
 */
class DB extends \PDO
{
    /** @var array[] database connection instances */
    private static array $instances = ['read' => [], 'write' => []];

    /** @var array PDO database connection options
     * @link https://www.php.net/manual/en/pdo.construct.php */
    private static array $connectionOptions = [
        \PDO::ATTR_ERRMODE            => \PDO::ERRMODE_EXCEPTION,
        \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
        \PDO::ATTR_EMULATE_PREPARES   => false
    ];

    /** @var string character set to use in DB connection */
    private static string $charSet = 'utf8mb4';

    /**
     * singleton method to return a database connection instance
     * 
     * This is equivalent to any previous method to "connect" to a database, although sometimes here you'll be accessing a
     * connection you already opened instead of spawning a brand new one each time. But, that shouldn't matter! The
     * important part is that it returns an instance of this class - your database connection.
     * @param string $db the name of the database to connect to
     * @param bool $write if write permissions are required. Use **only** for UPDATE, INSERT and DELETE queries.
     * @return DB
     */
    public static function getInstance(string $db, bool $write = false): DB
    {
        // During tests, always use the TransactionManager's connection if available
        // This ensures all database operations participate in the same transaction
        if (isset($_ENV['TEST_DISABLE_AUTOCOMMIT']) && $_ENV['TEST_DISABLE_AUTOCOMMIT'] === '1') {
            $activeConnection = \TN\TN_Core\Test\TransactionManager::getActiveConnection();
            if ($activeConnection !== null) {
                return $activeConnection;
            }
        }

        $type = $write ? 'write' : 'read';

        if (!isset(self::$instances[$type][$db])) {
            $credentials = self::getCredentials($write, $db);
            $dsn = 'mysql:host=' . $credentials['host'] . ";dbname=$db;charset=" . self::$charSet;

            // super important! This try/catch prevents database credentials being publically exposed
            // I know no-one including me understands PHP try/catch. But this one seems to be important!
            try {
                self::$instances[$type][$db] = new self($dsn, $credentials['user'], $credentials['pass'], self::$connectionOptions);

                // Disable autocommit for all database connections during tests
                if (isset($_ENV['TEST_DISABLE_AUTOCOMMIT']) && $_ENV['TEST_DISABLE_AUTOCOMMIT'] === '1') {
                    self::$instances[$type][$db]->exec("SET autocommit = 0");
                }
            } catch (\PDOException $e) {
                throw new \PDOException($e->getMessage(), (int)$e->getCode());
            }
        }

        return self::$instances[$type][$db];
    }

    /**
     * close all database connections by removing their references
     *
     * This does not guarantee however the connections will close. That happens once all references in all PHP memory to
     * them is removed. This method simply removes the references held statically on this class.
     */
    public static function closeConnections()
    {
        // removing the references here will close the connections
        self::$instances = [];
    }

    /**
     * Reset database to clean state for testing
     * 
     * This method provides a hook for test environments to reset the database
     * to a clean state. The default implementation does nothing - subclasses
     * or projects should override this method to implement their specific
     * reset logic (e.g., truncating tables, deleting test data, etc.).
     * 
     * @param string $database Database name to reset
     * @return void
     */
    public static function resetDatabase(string $database): void
    {
        // Default implementation does nothing
        // Projects should override this method or use a subclass
        // to implement their specific database reset logic
    }

    /**
     * get the database credentials from the TN ENV variable
     * @param bool $write if write permissions are required. Use **only** for UPDATE, INSERT and DELETE queries.
     * @param string $db
     * @return array
     */
    private static function getCredentials(bool $write = false, string $db = ''): array
    {
        if ($write) {
            return [
                'host' => $_ENV['MYSQL_ADMIN_HOST'],
                'user' => $_ENV['MYSQL_ADMIN_USER'],
                'pass' => $_ENV['MYSQL_ADMIN_PASS']
            ];
        } else {
            return [
                'host' => $_ENV['MYSQL_READ_HOST'],
                'user' => $_ENV['MYSQL_READ_USER'],
                'pass' => $_ENV['MYSQL_READ_PASS']
            ];
        }
    }
}

/** register close connections method on PHP shutdown, however that occurs */
register_shutdown_function([DB::class, 'closeConnections']);
