<?php

namespace TN\TN_Core\Model\UserDataModel;

use PDO;
use TN\TN_Core\Attribute\Constraints\Strlen;
use TN\TN_Core\Attribute\Impersistent;
use TN\TN_Core\Attribute\MySQL\TableName;
use TN\TN_Core\Attribute\MySQL\Timestamp;
use TN\TN_Core\Error\ValidationException;
use TN\TN_Core\Interface\Persistence;
use TN\TN_Core\Model\PersistentModel\PersistentModel;
use TN\TN_Core\Model\PersistentModel\Search\SearchArguments;
use TN\TN_Core\Model\PersistentModel\Search\SearchComparison;
use TN\TN_Core\Model\PersistentModel\Search\SearchSorter;
use TN\TN_Core\Model\PersistentModel\Storage\MySQL\MySQL;
use TN\TN_Core\Model\PersistentModel\Storage\MySQL\MySQLPrune;
use TN\TN_Core\Model\Storage\DB;
use TN\TN_Core\Model\Time\Time;

/**
 * used to reconcile client operations synchronized to the PHP persistent storage
 *
 */
#[TableName('userdata_operations')]
class Operation implements Persistence
{
    use MySQL;
    use PersistentModel;

    use MySQLPrune;

    protected static int $lifespan = Time::ONE_MONTH;
    protected static string $tsProp = 'appliedTs';

    const int CREATE = 1;
    const int UPDATE = 2;
    const int DELETE = 3;
    const int READ = 4;

    public int $userId;
    #[Strlen(0, 100)] public string $model;
    public int $method;
    public int $recordId;
    #[Strlen(0, 64)] public string $recordUuId;
    #[Timestamp] public int $appliedTs;
    #[Timestamp] public int $originTs;
    public ?string $prop;
    #[Impersistent] public mixed $value;
    #[Impersistent] public string $class;
    #[Impersistent] public mixed $record;
    #[Impersistent] public bool $applied;

    /** @return Operation[] */
    public static function getForUserSinceTs(int $userId, int $ts, array $models): array
    {
        return static::search(new SearchArguments(
            conditions: [
                new SearchComparison('`userId`', '=', $userId),
                new SearchComparison('`appliedTs`', '>', $ts),
                new SearchComparison('`model`', 'IN', $models)
            ],
            sorters: new SearchSorter('appliedTs', 'ASC')
        ));
    }

    protected function methodString(): string
    {
        return match ($this->method) {
            self::CREATE => 'create',
            self::UPDATE => 'update',
            self::DELETE => 'delete',
            default => 'unknown'
        };
    }

    /**
     * gets the record
     * @param string $class
     * @return UserDataModel|null
     */
    protected function getRecord(string $class): ?UserDataModel
    {
        $results = $class::readFromIdsOrUuIdsForUser($this->userId, $this->recordUuId);
        return empty($results) ? null : $results[0];
    }

    /**
     * operation represented in format that ExtJS clients require
     * @param string $class
     * @return array
     */
    public function getSyncDataForClient(string $class): array
    {
        $baseData = [
            'type' => $this->methodString(),
            'record_id' => $this->recordUuId,
            'model' => $this->model,
            'ts' => $this->appliedTs
        ];

        // if delete, we can handle that already!
        if ($this->method === self::DELETE) {
            return [$baseData];
        }

        // otherwise, get the record
        $record = $this->getRecord($class);

        // for create return that
        if ($this->method === self::CREATE) {
            return [array_merge($baseData, ['fields' => $record->getData(true)])];
        }

        // for update, return one operation per field
        $data = [];
        $ignoreProperties = $class::getClientIgnoreProperties();
        foreach (explode(',', $this->prop) as $prop) {
            if (in_array($prop, $ignoreProperties)) {
                continue;
            }
            $data[] = array_merge($baseData, ['field' => $class::mapPropertyNameToClient($prop), 'value' => $record->$prop]);
        }
        return $data;
    }

    /**
     * @return void
     * @throws ValidationException
     */
    public function customValidate(): void
    {
        if ($this->method === self::CREATE) {
            return;
        }

        // if there is otherwise a greater originTs for this specific record and userId and the same/similar action, don't do it
        $db = DB::getInstance($_ENV['MYSQL_DB'], true);
        $table = self::getTableName();
        $query = "
            SELECT COUNT(*)
            FROM {$table}
            WHERE `userId` = ?
            AND `recordId` = ?
            AND `model` = ?
            AND `originTs` > ?
            AND (`method` = ?";
        $params = [$this->userId, $this->recordId, $this->model, $this->originTs, $this->method];
        if ($this->method === self::UPDATE) {
            $query .= " OR `method` = ?)";
            $params[] = self::DELETE;
        } else {
            $query .= ")";
        }
        $stmt = $db->prepare($query);
        $stmt->execute($params);
        $result = $stmt->fetch(PDO::FETCH_NUM);
        if ((int)$result[0] > 0) {
            throw new ValidationException('Operation superceded by more recent originTs');
        }
    }

    protected function reducePreviousOperations(): void
    {
        if ($this->method === self::CREATE) {
            return;
        }

        $db = DB::getInstance($_ENV['MYSQL_DB'], true);
        $table = self::getTableName();
        $query = "
            DELETE
            FROM {$table}
            WHERE `userId` = ?
            AND `recordId` = ?
            AND `model` = ?
            AND `id` != ?";
        $params = [$this->userId, $this->recordId, $this->model, $this->id];

        if ($this->method === self::DELETE) {
            // delete: remove EVERYTHING previous EXCEPT the delete we just added
            // above query is great already!

        } else if ($this->method === self::UPDATE) {
            // update: remove any previous updates EXCEPT the update we just added
            $query .= " AND `method` = ? AND `originTs` <= ?";
            $params[] = self::UPDATE;
            $params[] = $this->originTs;
        }

        $db->prepare($query)->execute($params);
    }

    /**
     * apply the operation to the persistent storage
     * @return void
     */
    public function apply(): void
    {
        try {
            $this->customValidate();
        } catch (ValidationException) {
            return; // don't need to do this operation since it didn't pass validation
        }

        match ($this->method) {
            self::CREATE => $this->applyCreate(),
            self::UPDATE => $this->applyUpdate(),
            self::DELETE => $this->applyDelete()
        };

        // set appliedTs
        $this->appliedTs = Time::getNow();
        $this->applied = true;

        // now save the operation
        $this->save();

        // now we can remove some past operations!
        $this->reducePreviousOperations();
    }

    /**
     * apply creation
     * @return void
     */
    protected function applyCreate(): void
    {
        $this->record->save();
        if (isset($this->record->id)) {
            $this->recordId = $this->record->id;
        }
    }

    /**
     * apply update
     * @return void
     */
    protected function applyUpdate(): void
    {
        $this->record->save(explode(',', $this->prop));
    }

    /**
     * apply deletion
     * @return void
     */
    protected function applyDelete(): void
    {
        $this->record->erase();
    }
}
