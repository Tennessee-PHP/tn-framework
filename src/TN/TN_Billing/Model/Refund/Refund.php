<?php

namespace TN\TN_Billing\Model\Refund;

use PDO;
use TN\TN_Core\Attribute\Constraints\Inclusion;
use TN\TN_Core\Attribute\MySQL\TableName;
use TN\TN_Core\Attribute\Optional;
use TN\TN_Core\Interface\Persistence;
use TN\TN_Core\Model\PersistentModel\PersistentModel;
use TN\TN_Core\Model\PersistentModel\Storage\MySQL\MySQL;
use TN\TN_Core\Model\User\User;

/**
 * braintree credits
 * 
 */
#[TableName('refunds')]
class Refund implements Persistence
{
    use MySQL;
    use PersistentModel;
    public int $ts;
    public int $userId;
    public int $transactionId = 0;
    public string $transactionClass;
    public float $amount;
    #[Inclusion('()getReasonOptions|keys')] #[Optional] public string $reason = '';
    public string $comment = '';
    protected ?User $userRecord = null;

    public function __get(string $prop): mixed
    {
        if ($prop === 'user') {
            if ($this->userRecord === null) {
                $this->userRecord = User::readFromId($this->userId);
            }
            return $this->userRecord;
        }
        return null;
    }

    /** @return string[] get the possible reason options */
    public static function getReasonOptions(): array
    {
        return [];
    }

    /**
     * gets all transactions of this braintree type
     * @param User $user
     * @return array
     */
    public static function getFromUser(User $user): array
    {
        return self::searchByProperty('userId', $user->id);
    }

}