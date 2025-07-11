<?php

namespace TN\TN_Core\Model\CommandLog;

use PDO;
use TN\Model\Traits;
use TN\TN_Core\Attribute\MySQL\AutoIncrement;
use TN\TN_Core\Attribute\MySQL\PrimaryKey;
use TN\TN_Core\Attribute\MySQL\TableName;
use TN\TN_Core\Attribute\MySQL\Timestamp;
use TN\TN_Core\Interface\Persistence;
use TN\TN_Core\Model\Package\Stack;
use TN\TN_Core\Model\PersistentModel\PersistentModel;
use TN\TN_Core\Model\PersistentModel\Storage\MySQL\MySQL;
use TN\TN_Core\Model\PersistentModel\Storage\MySQL\MySQLPrune;
use TN\TN_Core\Model\Storage\DB;
use TN\TN_Core\Model\Time\Time;

/**
 * when cron runs a job, log the results
 *
 */
#[TableName('command_logs')]
class CommandLog implements Persistence
{
    use MySQL;
    use PersistentModel;
    use MySQLPrune;

    protected static int $lifespan = Time::ONE_WEEK;
    protected static string $tsProp = 'startTs';

    /** @var int start timestamp */
    #[Timestamp]
    public int $startTs = 0;

    /** @var int end timestamp */
    #[Timestamp]
    public int $endTs = 0;

    /** @var int how long it took */
    public int $duration = 0;

    /** @var string the argument name */
    public string $commandName = '';

    /** @var string the result */
    public string $result = '';

    /** @var bool has it completed already? */
    public bool $completed = false;

    /** @var bool was it successful? */
    public bool $success = false;
}
