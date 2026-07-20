<?php

namespace TN\TN_Billing\Model\Apple;

use TN\TN_Core\Attribute\MySQL\TableName;
use TN\TN_Core\Interface\Persistence;
use TN\TN_Core\Model\PersistentModel\PersistentModel;
use TN\TN_Core\Model\PersistentModel\Storage\MySQL\MySQL;
use TN\TN_Core\Model\PersistentModel\Storage\MySQL\MySQLPrune;
use TN\TN_Core\Model\Time\Time;

/**
 * Client-initiated native purchase validate/restore attempts (/apps/native-purchase-validation).
 */
#[TableName('native_purchase_validation_logs')]
class NativePurchaseValidationLog implements Persistence
{
    use MySQL;
    use PersistentModel;
    use MySQLPrune;

    protected static int $lifespan = Time::ONE_MONTH;
    protected static string $tsProp = 'startTs';

    public int $startTs = 0;
    public int $userId = 0;
    public string $platform = '';
    public string $productId = '';
    public string $appleTransactionId = '';
    public string $result = '';
    public bool $success = false;
}
