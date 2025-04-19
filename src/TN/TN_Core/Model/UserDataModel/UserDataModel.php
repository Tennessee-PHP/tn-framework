<?php

namespace TN\TN_Core\Model\UserDataModel;

use Ramsey\Uuid\Uuid as RamseyUuid;
use ReflectionClass;
use ReflectionProperty;
use TN\TN_Core\Attribute\UserData\ClientIgnore;
use TN\TN_Core\Attribute\UserData\UserData;
use TN\TN_Core\Error\DBException;
use TN\TN_Core\Interface\Persistence;
use TN\TN_Core\Model\Package\Stack;
use TN\TN_Core\Model\PersistentModel\PersistentModel;
use TN\TN_Core\Model\PersistentModel\Search\SearchArguments;
use TN\TN_Core\Model\PersistentModel\Search\SearchComparison;
use TN\TN_Core\Model\PersistentModel\Search\SearchLogical;
use TN\TN_Core\Model\PersistentModel\Storage\MySQL\MySQL;
use TN\TN_Core\Model\Time\Time;
use TN\TN_Core\Model\User\User;

abstract class UserDataModel implements Persistence
{
    use MySQL;
    use PersistentModel;

    public string $uuId;
    #[ClientIgnore] public int $userId;
    #[ClientIgnore] public int $year;

    protected function beforeSave(array $changedProperties): array
    {
        if (!isset($this->uuId)) {
            $this->uuId = (string)RamseyUuid::uuid4();
            return ['uuId'];
        }
        return [];
    }

    public static function resolveModelPathToClass(string $modelPath): ?string
    {
        foreach (Stack::getChildClasses(self::class) as $className) {
            try {
                $class = new ReflectionClass($className);
            } catch (\ReflectionException $e) {
                continue;
            }
            $attributes = $class->getAttributes(UserData::class);
            if (empty($attributes)) {
                continue;
            }
            $attribute = $attributes[0]->newInstance();
            if (strtolower($attribute->modelName) === strtolower($modelPath)) {
                return $className;
            }
        }

        return null;
    }

    /** @returns static[] */
    public static function readFromIdsOrUuIdsForUser(int $userId, array|string|int $ids): array
    {
        if (!is_array($ids)) {
            $ids = [$ids];
        }
        return static::search(new SearchArguments([
            new SearchLogical('AND', [
                new SearchComparison('`userId`', '=', $userId),
                new SearchLogical('OR', [
                    new SearchComparison('`id`', 'IN', $ids),
                    new SearchComparison('`uuId`', 'IN', $ids)
                ])
            ])
        ]));
    }

    public static function readMultipleForUser(int $userId, array $extraSearch = []): array
    {
        $search = array_merge(['userId' => $userId], $extraSearch);
        $records = self::searchByProperties($search);

        // sort by ID
        $ids = [];
        foreach ($records as $item) {
            $ids[] = $item->id;
        }
        array_multisort($ids, SORT_ASC, $records);
        return $records;
    }

    /**
     * @return string the value of the UserData attribute on this class or the empty string
     */
    public static function getUserDataModelName(): string
    {
        $class = get_called_class();
        try {
            $class = new ReflectionClass($class);
        } catch (\ReflectionException $e) {
            return '';
        }
        $attributes = $class->getAttributes(UserData::class);
        if (empty($attributes)) {
            return '';
        }
        $attribute = $attributes[0]->newInstance();
        return $attribute->modelName;
    }



    /**
     * @param User $user
     * @param int $originTs
     * @param int $method
     * @param mixed $record
     * @param string[] $props for update ops, changed properties
     * @return Operation
     */
    protected static function getOperation(User $user, int $originTs, int $method, mixed $record, array $props = []): Operation
    {
        $operation = Operation::getInstance();
        $operation->originTs = $originTs;
        $operation->userId = $user->id;
        $operation->model = self::getModelName();
        $operation->method = $method;
        $operation->record = $record;
        $operation->applied = false;
        if ($operation->method === Operation::UPDATE) {
            $operation->prop = implode(',', $props);
        }
        if (isset($record->id)) {
            $operation->recordId = $record->id;
            $operation->recordUuId = $record->uuId;
        }
        if (isset($record->uuId)) {
            $operation->recordUuId = $record->uuId;
        }
        return $operation;
    }

    /**
     * @param UserDataModel $record
     * @param array $data
     * @param bool $fromClient
     * @return string[] array of properties changed
     */
    protected static function setData(UserDataModel $record, array $data, bool $fromClient): array
    {
        // DEBUG
        file_put_contents(
            '/Users/simonshepherd/footballguys/fbgsite/tmp/userdata-debug.log',
            "UserDataModel::setData called\n" .
                "Class: " . get_called_class() . "\n" .
                "Data: " . json_encode($data) . "\n" .
                "fromClient: " . ($fromClient ? 'true' : 'false') . "\n",
            FILE_APPEND
        );

        // put the data onto the record
        if ($fromClient) {
            $data = self::getDataFromClient($data);
            // DEBUG
            file_put_contents(
                '/Users/simonshepherd/footballguys/fbgsite/tmp/userdata-debug.log',
                "After getDataFromClient: " . json_encode($data) . "\n",
                FILE_APPEND
            );
        }

        // add a uuId if one doesn't exist
        if (!isset($data['uuId']) && !isset($record->uuId)) {
            $data['uuId'] = (string)RamseyUuid::uuid4();
        }

        $changedProps = [];

        foreach ($data as $prop => $value) {
            if ($prop !== 'id') {
                try {
                    $rp = new ReflectionProperty(get_called_class(), $prop);

                    // DEBUG
                    file_put_contents(
                        '/Users/simonshepherd/footballguys/fbgsite/tmp/userdata-debug.log',
                        "Processing property: $prop\n",
                        FILE_APPEND
                    );

                    $value = match ($rp->getType()->getName()) {
                        'int' => (int)$value,
                        'float' => (float)$value,
                        'array' => is_array($value) ? $value : [],
                        'string' => is_array($value) ? '' : (string)$value,
                        default => $value
                    };

                    if (!isset($record->$prop) || $record->$prop != $value) {
                        $record->$prop = $value;
                        $changedProps[] = $prop;

                        // DEBUG
                        file_put_contents(
                            '/Users/simonshepherd/footballguys/fbgsite/tmp/userdata-debug.log',
                            "Changed property: $prop to " . json_encode($value) . "\n",
                            FILE_APPEND
                        );
                    }
                } catch (\ReflectionException) {
                    // DEBUG
                    file_put_contents(
                        '/Users/simonshepherd/footballguys/fbgsite/tmp/userdata-debug.log',
                        "Property $prop does not exist in class\n",
                        FILE_APPEND
                    );
                    continue;
                }
            }
        }

        // DEBUG
        file_put_contents(
            '/Users/simonshepherd/footballguys/fbgsite/tmp/userdata-debug.log',
            "Changed properties: " . json_encode($changedProps) . "\n" .
                "-----------------------------\n",
            FILE_APPEND
        );

        return $changedProps;
    }

    /**
     * create a record based on client data
     * @param User $user
     * @param int $originTs
     * @param array $data
     * @param bool $fromClient
     * @param bool $apply
     * @return Operation
     */
    public static function createUserData(User $user, int $originTs, array $data, bool $fromClient = false, bool $apply = true): Operation
    {
        // FIRST, set up the record
        $record = self::getInstance();
        $record->userId = $user->id;
        $record->year = date('Y', $originTs);

        // and set its data
        self::setData($record, $data, $fromClient);

        // SECOND, set up the operation
        $operation = self::getOperation($user, $originTs, Operation::CREATE, $record);

        // THIRD, optionally apply the operation
        if ($apply) {
            $operation->apply();
        }

        return $operation;
    }

    /**
     * apply a set of updates based on client data
     * @param User $user
     * @param int $originTs
     * @param array $data ['num_teams' => 12]
     * @param bool $fromClient
     * @param bool $apply
     * @return Operation
     */
    public function updateUserData(User $user, int $originTs, array $data, bool $fromClient = false, bool $apply = true): Operation
    {
        // FIRST, set the new data
        // CRITICAL FIX: Use static:: instead of self:: to properly respect inheritance and method overrides
        $props = static::setData($this, $data, $fromClient);

        // SECOND, create the operation, passing through the array of changed properties
        $operation = self::getOperation($user, $originTs, Operation::UPDATE, $this, $props);

        // THIRD, optionally apply the operation
        if ($apply) {
            $operation->apply();
        }

        return $operation;
    }

    /**
     * @param User $user
     * @param int $originTs
     * @param bool $apply
     * @return Operation
     */
    public function deleteUserData(User $user, int $originTs, bool $apply = true): Operation
    {
        $operation = self::getOperation($user, $originTs, Operation::DELETE, $this);

        // THIRD, optionally apply the operation
        if ($apply) {
            $operation->apply();
        }

        return $operation;
    }

    /**
     * @param string $property e.g. 'reauthNeeded'
     * @return string e.g. 'reauth_needed'
     */
    public static function mapPropertyNameToClient(string $property): string
    {
        if ($property === 'uuId') {
            return 'id';
        }

        if (in_array($property, ['projectionType', 'uniqueBy', 'teamLimits', 'maxCap', 'minCap'])) {
            return $property;
        }

        $parts = preg_split("/([A-Z])/", $property, -1, PREG_SPLIT_DELIM_CAPTURE);
        $str = '';
        foreach ($parts as $i => $part) {
            // is this a single uppercase letter?
            if (strtoupper($part) === $part && strlen($part) === 1) {
                // was a delimiter!
                $str .= '_' . strtolower($part);
            } else {
                $str .= strtolower($part);
            }
        }
        return trim($str);
    }

    /**
     * @param string $property e.g. 'reauth_needed'
     * @return string e.g. 'reauthNeeded'
     */
    public static function mapPropertyNameFromClient(string $property): string
    {
        if ($property === 'id') {
            return 'uuId';
        }

        if (in_array($property, ['projectionType', 'uniqueBy', 'teamLimits', 'maxCap', 'minCap'])) {
            return $property;
        }

        // first, capitalize all letters that immediately follow an underscore
        $words = explode('_', $property);
        foreach ($words as $i => &$word) {
            $word = strtolower($word);
            if ($i === 0) {
                continue;
            }
            $word = ucfirst($word);
            if (empty($word)) {
                $word = '_';
            }
        }
        return trim(implode('', $words));
    }

    /**
     * @return string[] property names that should not be returned to the client
     */
    protected static function getClientIgnoreProperties(): array
    {
        $class = new \ReflectionClass(get_called_class());
        $clientIgnoreProperties = [];
        foreach ($class->getProperties() as $property) {
            $propertyName = $property->getName();
            if (!empty(array_merge($property->getAttributes(ClientIgnore::class)))) {
                $clientIgnoreProperties[] = $propertyName;
            }
        }
        return $clientIgnoreProperties;
    }

    /**
     * returns the object's data as an array
     * @param bool $forClient
     * @return array
     */
    public function getData(bool $forClient = false): array
    {
        $data = get_object_vars($this);
        if (!$forClient) {
            return $data;
        }
        unset($data['id']);
        $extJsData = [];

        // calculate a list of properties that are client ignore
        $clientIgnoreProperties = self::getClientIgnoreProperties();

        foreach ($data as $prop => $value) {
            // check if the property has the client ignore attribute
            if (in_array($prop, $clientIgnoreProperties)) {
                continue;
            }
            $extJsData[self::mapPropertyNameToClient($prop)] = $value;
        }
        $extJsData['model'] = self::getModelName();
        return $extJsData;
    }

    /**
     * @param array $extJsData
     * @return array
     */
    public static function getDataFromClient(array $extJsData): array
    {
        $data = [];
        foreach ($extJsData as $prop => $value) {
            $data[self::mapPropertyNameFromClient($prop)] = $value;
        }
        return $data;
    }

    /**
     * @return string
     */
    public static function getModelName(): string
    {
        // examine this class for the user data attribute and get its model
        $className = Stack::resolveClassName(get_called_class());
        try {
            return (new \ReflectionClass($className))->getAttributes(UserData::class)[0]->newInstance()->modelName;
        } catch (\Exception) {
            return '';
        }
    }
}
