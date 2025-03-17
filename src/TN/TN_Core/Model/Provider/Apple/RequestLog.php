<?php

namespace TN\TN_Core\Model\Provider\Apple;

use TN\TN_Core\Attribute\MySQL\TableName;
use TN\TN_Core\Interface\Persistence;
use TN\TN_Core\Model\PersistentModel\PersistentModel;
use TN\TN_Core\Model\PersistentModel\Storage\MySQL\MySQL;
use TN\TN_Core\Model\PersistentModel\Storage\MySQL\MySQLPrune;
use TN\TN_Core\Model\Time\Time;

/**
 *
 */
#[TableName('apple_request_logs')]
class RequestLog implements Persistence
{
    use MySQL;
    use PersistentModel;
    use MySQLPrune;

    protected static int $lifespan = Time::ONE_MONTH;
    protected static string $tsProp = 'startTs';

    public int $startTs = 0;
    public int $endTs = 0;
    public int $duration = 0;
    public string $request = '';
    public string $result = '';
    public string $notificationType = '';
    public string $transactionInfo = '';
    public bool $completed = false;
    public bool $success = false;

}