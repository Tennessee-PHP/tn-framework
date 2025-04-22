<?php

namespace TN\TN_Core\Model\UserDataModel;

use FBG\FBG_NFL\Model\Calendar\Season;
use TN\TN_Core\Error\ValidationException;
use TN\TN_Core\Error\DBException;
use TN\TN_Core\Model\Time\Time;
use TN\TN_Core\Model\User\User;

/**
 * handles multiple operations at once
 *
 */
class OperationSet
{
    /** @var Operation[] */
    public array $operations;
    public array $records;
    public int $recTs;
    public int $lastSyncTs;
    public ?array $classes = null;
    public array $userOperationsSinceLastSync = [];

    /**
     * @param array $operations
     * @return OperationSet
     */
    protected static function getFromOperations(array $operations): OperationSet
    {
        return new self($operations);
    }

    /**
     * get the logged in user
     * @return User
     */
    protected static function getUser(): User
    {
        return User::getActive();
    }

    /**
     * @param array $classes
     * @return array
     */
    protected static function getModelToClassMap(array $classes): array
    {
        $map = [];
        foreach ($classes as $class) {
            $map[strtolower($class::getUserDataModelName())] = $class;
        }
        return $map;
    }

    /**
     * find a record from the existingRecordsByClass structure
     * @param array $existingRecordsByClass
     * @param string $class
     * @param string|int $id
     * @return UserDataModel|null
     */
    protected static function lookupRecordByClassAndId(array $existingRecordsByClass, string $class, string|int $id): ?UserDataModel
    {
        $classRecords = $existingRecordsByClass[$class] ?? [];
        return $classRecords[$id] ?? null;
    }

    /**
     * @param int $lastSyncTs
     * @param array $operationsData
     * @param array|null $classes
     * @return OperationSet
     * gets an operation set from sync data from a client
     * @throws \TN\TN_Core\Error\ValidationException
     */
    public static function getFromSyncData(int $lastSyncTs, array $operationsData, ?array $classes = null): OperationSet
    {
        // fetch all the records that are already created on the server and referenced
        $modelToClassMap = self::getModelToClassMap($classes ?? []);
        $recordsToReadByClass = [];

        foreach ($operationsData as &$opData) {
            // Normalize timestamp to seconds if it's in milliseconds
            if (isset($opData['ts']) && strlen((string)$opData['ts']) === 13) {
                $opData['ts'] = (int)($opData['ts'] / 1000);
            }

            // try to find the class for this op
            $class = $modelToClassMap[trim(strtolower($opData['model']))] ?? false;

            if (!$class || !$opData['record_id']) {
                continue;
            }

            // add the record_id to that array
            if (!isset($recordsToReadByClass[$class])) {
                $recordsToReadByClass[$class] = [];
            }
            $recordsToReadByClass[$class][] = $opData['record_id'];
        }

        $existingRecordsByClass = [];
        foreach ($recordsToReadByClass as $class => $ids) {
            $records = self::readRecords($class, array_unique($ids));
            $existingRecordsByClass[$class] = [];
            foreach ($records as $record) {
                $existingRecordsByClass[$class][$record->uuId] = $record;
            }
        }

        // let's sort the data incoming so we always deal with the smallest TS first, ASC
        $operationTimestamps = [];
        foreach ($operationsData as $opData) {
            $operationTimestamps[] = $opData['ts'];
        }
        array_multisort($operationTimestamps, SORT_ASC, $operationsData);

        // create all the operations. As creates are handled, add to existingRecordsByClass array
        $ops = [];

        // TODO QUESTION are timestamps sent by ExtJS in milliseconds or not?

        foreach ($operationsData as $opData) {
            $class = $modelToClassMap[trim(strtolower($opData['model']))] ?? false;
            if (!$class) {
                continue;
            }

            // create? then create! BUT remember to add the record
            if (strtolower($opData['type']) === 'create') {
                $op = $class::createUserData(self::getUser(), $opData['ts'], $opData['fields'], true, false);
                $existingRecordsByClass[$class][$op->record->uuId] = $op->record;
                $ops[] = $op;
            } else {
                // otherwise let's try to get the record
                $record = self::lookupRecordByClassAndId($existingRecordsByClass, $class, $opData['record_id']);
                if (!$record) {
                    continue;
                }
                if (strtolower($opData['type']) === 'update') {
                    $op = $record->updateUserData(self::getUser(), $opData['ts'], [$opData['field'] => $opData['value']], true, false);
                    if ($op) {
                        $ops[] = $op;
                    }
                } else if (strtolower($opData['type']) === 'delete') {
                    $ops[] = $record->deleteUserData(self::getUser(), $opData['ts'], false);
                }
            }
        }

        // create the operation set, setting the classes on it too
        return new self($ops, $lastSyncTs, $classes);
    }

    /**
     * @param string $class
     * @param array $data
     * @param bool $fromClient
     * @return OperationSet
     * @assert returns an operation set with the same number of operations as the size of the $data array
     * @assert has not applied any of the operations
     * @assert if fromClient = true, has translated the property names from client form
     * @assert if fromClient = false, has not translated the property names from client form
     * @assert the model on each operation should be set correctly, ie, if class is HostAccount::class, model should be "hostaccount"
     * @assert method on each operation should be set to create
     * @assert if no data is passed in, operation set with zero operations should be returned
     */
    public static function getCreateOperationsFromData(string $class, array $data, bool $fromClient = false): OperationSet
    {
        // Sort data into two categories: those with UUIDs and those without
        $dataWithUuIds = [];
        $dataWithoutUuIds = [];
        $uuIds = [];

        // Process all data items
        foreach ($data as $item) {
            if (isset($item['uuId']) || isset($item['id'])) {
                $uuId = $item['uuId'] ?? $item['id'];
                $dataWithUuIds[$uuId] = $item;
                $uuIds[] = $uuId;
            } else {
                $dataWithoutUuIds[] = $item;
            }
        }

        $ops = [];
        $user = self::getUser();
        $originTs = Time::getNow();

        // Check for existing records with these UUIDs
        $existingRecords = [];
        if (!empty($uuIds)) {
            $existingRecords = $class::readFromIdsOrUuIdsForUser($user->id, $uuIds);
        }

        // Process existing records as updates
        foreach ($existingRecords as $record) {
            $updateData = $dataWithUuIds[$record->uuId] ?? [];

            // Remove the UUID from the update data to prevent warnings
            unset($updateData['uuId']);
            unset($updateData['id']);

            // Create an update operation for this record
            $ops[] = $record->updateUserData($user, $originTs, $updateData, $fromClient, false);

            // Remove the processed item so we don't create it later
            unset($dataWithUuIds[$record->uuId]);
        }

        // Process remaining items with UUIDs as creates
        foreach ($dataWithUuIds as $item) {
            $ops[] = $class::createUserData($user, $originTs, $item, $fromClient, false);
        }

        // Process items without UUIDs as creates
        foreach ($dataWithoutUuIds as $item) {
            $ops[] = $class::createUserData($user, $originTs, $item, $fromClient, false);
        }

        return self::getFromOperations($ops);
    }

    /**
     * @param string $class
     * @param array $data
     * @param bool $fromClient
     * @return OperationSet
     */
    public static function getUpdateOperationsFromData(string $class, array $data, bool $fromClient = false): OperationSet
    {
        $ids = [];
        $dataById = [];
        foreach ($data as $item) {
            $id = $item['id'] ?? $item['uuId'];
            unset($item['id']);
            unset($item['uuid']);
            unset($item['uuId']);
            $ids[] = $id;
            $dataById[$id] = $item;
        }

        $ops = [];
        $user = self::getUser();
        $originTs = Time::getNow();
        foreach (self::readRecords($class, $ids) as $record) {
            $updateData = $dataById[$record->id] ?? $dataById[$record->uuId];
            $ops[] = $record->updateUserData($user, $originTs, $updateData, $fromClient, false);
        }
        return self::getFromOperations($ops);
    }

    /**
     * reads the records required so that we can create the operations
     * @param string $class
     * @param array $ids
     * @return array
     */
    protected static function readRecords(string $class, array $ids): array
    {
        return empty($ids) ? [] : $class::readFromIdsOrUuIdsForUser(self::getUser()->id, $ids);
    }

    /**
     * constructor
     * @param array $operations
     * @param int|null $lastSyncTs
     * @param array|null $classes
     */
    protected function __construct(array $operations, ?int $lastSyncTs = 0, ?array $classes = null)
    {
        $this->lastSyncTs = $lastSyncTs;
        $this->classes = $classes;
        $this->operations = $operations;
        $this->records = [];
        $this->recTs = Time::getNow();
        foreach ($operations as $operation) {
            $this->records[] = $operation->record;
        }
    }

    /**
     * groups operations into an array indexed first by their model and then their UuId
     * @return array
     */
    protected function getOperationsByModelAndUuId(): array
    {
        // [model][uuId]
        $operations = [];
        foreach ($this->operations as $operation) {
            if (!isset($operations[$operation->model])) {
                $operations[$operation->model] = [];
            }
            if (!isset($operations[$operation->model][$operation->record->uuId])) {
                $operations[$operation->model][$operation->record->uuId] = [];
            }
            $operations[$operation->model][$operation->record->uuId][] = $operation;
        }
        return $operations;
    }

    /**
     * @param array $operations
     * @return Operation|null
     * @todo Simon
     * reconciles a collection of operations all on a single record down to one single operation,
     * e.g. create + update = single create,
     * e.g. update + delete = single delete etc.
     */
    protected function getSingleOperationToApplyForRecord(array $operations): ?Operation
    {
        // this is where a sync has sent us a ton of stuff, we can always reconcile to one op per record
        $createOp = null;
        $deleteOp = null;
        $updateOps = [];

        // sort! by method DESC
        foreach ($operations as $op) {
            if ($op->method === Operation::CREATE) {
                $createOp = $op;
            } else if ($op->method === Operation::DELETE) {
                $deleteOp = $op;
            } else if ($op->method === Operation::UPDATE) {
                $updateOps[] = $op;
            }
        }

        // delete and create? return null
        if ($createOp && $deleteOp) {
            return null;
        }

        // split out any delete (and ignore everything else for that model)
        if ($deleteOp) {
            return $deleteOp;
        }

        // if creates and updates, all the updates will already be on it since we re-used the record instances in getFromSyncData
        if ($createOp) {
            return $createOp;
        }

        if (empty($updateOps)) {
            return null;
        }

        // merge together update ops into one single update op
        $updateOp = null;
        foreach ($updateOps as $i => $updateOpI) {
            if ($i === 0) {
                $updateOp = $updateOpI;
                continue;
            }

            // merge it down onto updateOp
            $updateOp->prop = implode(',', array_merge(explode(',', $updateOp->prop), explode(',', $updateOpI->prop)));
        }

        return $updateOp;
    }

    /**
     * apply all these operations to the database. Return itself for chaining on routes
     * @return OperationSet
     * @throws DBException
     */
    public function apply(): OperationSet
    {

        foreach ($this->getOperationsByModelAndUuId() as $model => $recordOperations) {

            $operationsByMethod = [];
            foreach ($recordOperations as $uuId => $operations) {

                $operation = $this->getSingleOperationToApplyForRecord($operations);

                if (!$operation) {
                    continue;
                }
                if (!isset($operationsByMethod[$operation->method])) {
                    $operationsByMethod[$operation->method] = [];
                }
                $operationsByMethod[$operation->method][] = $operation;
            }

            foreach ($operationsByMethod as $method => $operations) {

                $this->batchApplyOperationsOnModel($method, $operations);
            }
        }

        return $this;
    }

    /**
     * @param int $method
     * @param array $operations
     * @return void
     * @throws DBException
     */
    protected function batchApplyOperationsOnModel(int $method, array $operations): void
    {
        match ($method) {
            Operation::CREATE => $this->batchApplyCreateOperationsOnModel($operations),
            Operation::UPDATE => $this->batchApplyUpdateOperationsOnModel($operations),
            Operation::DELETE => $this->batchApplyDeleteOperationsOnModel($operations),
            default => null
        };
    }

    /**
     * @param array $operations
     * @return void
     */
    protected function batchApplyCreateOperationsOnModel(array $operations): void
    {
        if (empty($operations)) {
            return;
        }
        $class = get_class($operations[0]->record);
        $records = [];
        $applyOperations = [];
        foreach ($operations as $operation) {
            try {
                $operation->customValidate();
            } catch (ValidationException) {
                continue;
            }
            $records[] = $operation->record;
            $applyOperations[] = $operation;
        }

        $class::batchSaveInsert($records);

        foreach ($applyOperations as $operation) {
            $operation->recordId = $operation->record->id;
            $operation->applied = true;
            $operation->appliedTs = Time::getNow();
        }

        Operation::batchSaveInsert($applyOperations);
    }

    /**
     * @param array $operations
     * @return void
     */
    protected function batchApplyUpdateOperationsOnModel(array $operations): void
    {
        foreach ($operations as $operation) {
            $operation->apply();
        }
    }

    /**
     * @param array $operations
     * @return void
     * @throws DBException
     */
    protected function batchApplyDeleteOperationsOnModel(array $operations): void
    {
        if (empty($operations)) {
            return;
        }
        $class = get_class($operations[0]->record);
        $records = [];
        $applyOperations = [];
        foreach ($operations as $operation) {
            try {
                $operation->customValidate();
            } catch (ValidationException) {
                continue;
            }
            $records[] = $operation->record;
            $applyOperations[] = $operation;
        }

        $class::batchErase($records);
        foreach ($applyOperations as $operation) {
            $operation->applied = true;
            $operation->appliedTs = Time::getNow();
        }
        Operation::batchSaveInsert($applyOperations);
    }

    /**
     * gets the record data resultant
     * @param bool $forClient
     * @return array
     */
    public function getResultantRecordData(bool $forClient = false): array
    {
        // if only one element, just return it
        if (count($this->records) === 1) {
            return $this->records[0]->getData($forClient);
        }

        // otherwise return the array
        $data = [];
        foreach ($this->records as $record) {
            $data[] = $record->getData($forClient);
        }
        return $data;
    }

    /**
     * @return array
     */
    public function getReturnSyncData(): array
    {
        if ($this->sendFullSync()) {
            return [
                'data' => $this->getFullSyncData(),
                'ts' => $this->recTs
            ];
        } else {
            return [
                'operations' => $this->userOperationsSinceLastSync,
                'ts' => $this->recTs
            ];
        }
    }

    /**
     * should we do a full sync or not?
     * @return bool
     */
    public function sendFullSync(): bool
    {
        return
            // if the last sync is 0, do it
            !$this->lastSyncTs
            ||
            // if the last sync is more than 30 days ago
            $this->lastSyncTs < (Time::getNow() - Time::ONE_DAY * 30)
            ||
            // we should do this if the season is different from the last sync
            Season::getFromTs(Time::getNow())->year !== Season::getFromTs($this->lastSyncTs)->year;
    }

    /**
     * @return array
     */
    public function getModels(): array
    {
        $models = [];
        foreach ($this->classes as $class) {
            $models[] = $class::getUserDataModelName();
        }
        return $models;
    }

    /**
     * @return array
     */
    public function getModelMap(): array
    {
        $map = [];
        foreach ($this->classes as $class) {
            $map[$class::getUserDataModelName()] = $class;
        }
        return $map;
    }

    /**
     * gets the operations that the client isn't aware of yet
     * @return array
     */
    public function setUserOperationsSinceLastSync(): array
    {
        // make sure to split out updates one per field
        $operations = Operation::getForUserSinceTs($this->getUser()->id, $this->lastSyncTs, $this->getModels());
        $data = [];
        $map = $this->getModelMap();
        foreach ($operations as $operation) {
            $data = array_merge($data, $operation->getSyncDataForClient($map[$operation->model]));
        }

        return $data;
    }

    /**
     * @return array
     */
    protected function getFullSyncData(): array
    {
        $data = [];
        foreach ($this->classes as $class) {
            $data = array_merge($data, $class::readMultipleForUser($this->getUser()->id));
        }
        foreach ($data as &$item) {
            $item = $item->getData(true);
        }
        return $data;
    }
}
