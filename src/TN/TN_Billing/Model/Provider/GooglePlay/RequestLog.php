<?php

namespace TN\TN_Billing\Model\Provider\GooglePlay;

use TN\TN_Core\Attribute\MySQL\TableName;
use TN\TN_Core\Interface\Persistence;
use TN\TN_Core\Model\PersistentModel\PersistentModel;
use TN\TN_Core\Model\PersistentModel\Storage\MySQL\MySQL;
use TN\TN_Core\Model\PersistentModel\Storage\MySQL\MySQLPrune;
use TN\TN_Core\Model\Time\Time;

/**
 * when cron runs a job, log the results
 *
 */
#[TableName('googleplay_request_logs')]
class RequestLog implements Persistence
{
    use MySQL;
    use PersistentModel;
    use MySQLPrune;

    protected static int $lifespan = Time::ONE_MONTH;
    protected static string $tsProp = 'startTs';

    protected int $startTs = 0;
    protected int $endTs = 0;
    protected int $duration = 0;
    protected string $request = '';
    protected string $result = '';
    protected bool $completed = false;
    protected bool $success = false;

}