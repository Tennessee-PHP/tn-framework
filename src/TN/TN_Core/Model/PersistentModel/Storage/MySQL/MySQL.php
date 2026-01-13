<?php

namespace TN\TN_Core\Model\PersistentModel\Storage\MySQL;

use Exception;
use PDO;
use TN\TN_Core\Attribute\Constraints\NumberRange;
use TN\TN_Core\Attribute\Constraints\Strlen;
use TN\TN_Core\Attribute\Impersistent;
use TN\TN_Core\Attribute\MySQL\AutoIncrement;
use TN\TN_Core\Attribute\MySQL\ForeignKey;
use TN\TN_Core\Attribute\MySQL\Index;
use TN\TN_Core\Attribute\MySQL\PrimaryKey;
use TN\TN_Core\Attribute\MySQL\TableName;
use TN\TN_Core\Attribute\MySQL\Timestamp;
use TN\TN_Core\Attribute\Relationships\ChildrenClass;
use TN\TN_Core\Attribute\Relationships\ParentObject;
use TN\TN_Core\Error\DBException;
use TN\TN_Core\Model\Package\Stack;
use TN\TN_Core\Model\PersistentModel\SaveType;
use TN\TN_Core\Model\PersistentModel\Search\CountAndTotalResult;
use TN\TN_Core\Model\PersistentModel\Search\SearchArguments;
use TN\TN_Core\Model\Storage\DB;
use TN\TN_Core\Trait\PerformanceRecorder;

/** @var int set to 1 to echo all queries and how long they took */
const MYSQL_DEBUG_MODE = 0;

/**
 * stores the object in mysql
 */
trait MySQL
{
    use PerformanceRecorder;

    /**
     * get the name of the property that has the AutoIncrement attribute
     * @return string
     */
    public static function getAutoIncrementProperty(): string
    {
        $class = new \ReflectionClass(get_called_class());
        foreach ($class->getProperties() as $property) {
            $attributes = $property->getAttributes(AutoIncrement::class);
            if (count($attributes) > 0) {
                return $property->getName();
            }
        }

        // default to simply id
        return 'id';
    }

    /**
     * performs a batch erase on the database for all the objects
     * @return void
     * @throws Exception
     */
    public static function batchEraseAll(): void
    {
        if ($_ENV['ENV'] !== 'test') {
            throw new Exception('I don\'t know who you think you are but you\'re not codeception so you may not do this!!!!');
        }
        $db = DB::getInstance($_ENV['MYSQL_DB'], true);
        $table = self::getTableName();
        self::invalidateClassCache();
        $db->query("DELETE FROM {$table}");
    }

    /**
     * performs a batch erase on the database for all the objects provided
     * @param array $objects
     * @return void
     * @throws DBException
     */
    protected static function batchEraseStorage(array $objects): void
    {
        $db = DB::getInstance($_ENV['MYSQL_DB'], true);
        $table = self::getTableName();
        $idProp = self::getAutoIncrementProperty();
        $objectIds = [];

        foreach ($objects as $object) {
            if (isset($object->$idProp)) {
                $objectIds[] = $object->$idProp;
            }
        }
        if (empty($objectIds)) {
            return;
        }

        $query = "DELETE FROM {$table} WHERE {$idProp} IN (" .
            implode(',', array_fill(0, count($objectIds), '?')) . ")";
        $event = self::startPerformanceEvent('MySQL', $query, ['params' => $objectIds]);

        if (MYSQL_DEBUG_MODE) {
            echo $query;
        }

        $res = $db->prepare($query)->execute($objectIds);

        if (!$res) {
            throw new DBException('Failed to execute batch erase query');
        }

        $event?->end();
    }

    /**
     * insert multiple objects in one query
     * @param array $objects
     * @param bool $useSetId set the record's id in the database to be its current value
     * @return void
     * @throws DBException
     */
    protected static function batchSaveInsertStorage(array $objects, bool $useSetId = false): void
    {
        if (empty($objects)) {
            return;
        }
        $db = DB::getInstance($_ENV['MYSQL_DB'], true);
        $table = self::getTableName();
        $properties = self::getPersistentProperties();
        $placeHolders = implode(',', array_fill(0, count($properties), '?'));
        $values = [];
        $props = [];
        $idProp = self::getAutoIncrementProperty();
        foreach ($objects as $i => $object) {
            if ($useSetId && isset($object->$idProp)) {
                $values[] = $object->$idProp;
                if ($i === 0) {
                    $placeHolders .= ',?';
                    $props[] = '`id`';
                }
            }
            foreach ($properties as $prop) {
                $values[] = $object->savePropertyValue($prop, $object->$prop ?? null);
                if ($i === 0) {
                    $props[] = '`' . $prop . '`';
                }
            }
        }

        $idProp = self::getAutoIncrementProperty();

        $properties = implode(', ', $props);
        $query = "INSERT INTO {$table} ({$properties}) VALUES " .
            implode(',', array_fill(0, count($objects), '(' . $placeHolders . ')'));
        $event = self::startPerformanceEvent('MySQL', $query, ['params' => $values, 'objectCount' => count($objects)]);

        if (MYSQL_DEBUG_MODE) {
            echo $query . PHP_EOL;
        }

        $stmt = $db->prepare($query);

        try {
            $stmt->execute($values);
        } catch (\PDOException $e) {
            throw new DBException(static::class . ': ' . $e->getMessage());
        }

        $event?->end();

        // let's check the number
        if ($stmt->rowCount() !== count($objects)) {
            throw new DBException('Inserted row count does not match object size (' . $stmt->rowCount() . '/' . count($objects) . ')');
        }
        $insertedId = (int)$db->lastInsertId();
        foreach ($objects as $object) {
            if (!$useSetId || !isset($object->$idProp)) {
                $object->$idProp = $insertedId;
            }
            $insertedId += 1;
        }
    }

    /**
     * get the table name
     * @return string
     */
    public static function getTableName(): string
    {
        $class = new \ReflectionClass(get_called_class());
        $tableNameAttributes = [];
        while ($class && empty($tableNameAttributes)) {
            $tableNameAttributes = $class->getAttributes(TableName::class);
            $class = $class->getParentClass();
        }
        if (empty($tableNameAttributes)) {
            trigger_error('Class ' . Stack::resolveClassName(get_called_class()) .
                ' uses the MySQL trait but does not set a table name with use of the TableName attribute');
        }
        $tableName = $tableNameAttributes[0]->newInstance();
        return $tableName->name;
    }

    /**
     * @throws DBException
     */
    public static function countStorage(SearchArguments $search, bool $absoluteLatest = false): int
    {
        $search->limit = null;
        $select = new MySQLSelect(
            table: self::getTableName(),
            className: static::class,
            selectType: MySQLSelectType::Count,
            search: $search
        );

        try {
            $event = self::startPerformanceEvent('MySQL', $select->query, ['params' => $select->params]);

            $db = DB::getInstance($_ENV['MYSQL_DB'], $absoluteLatest);
            $stmt = $db->prepare($select->query);

            if (MYSQL_DEBUG_MODE) {
                echo $select->query . PHP_EOL;
            }

            if (!$stmt->execute($select->params)) {
                throw new DBException('Failed to execute count query');
            }

            $result = $stmt->fetch(PDO::FETCH_NUM);
            $result = (int)$result[0];

            $event?->end();
        } catch (\PDOException $e) {
            throw new DBException($e->getMessage());
        }

        return $result;
    }

    /**
     * @throws DBException
     */
    public static function countAndTotalStorage(SearchArguments $search, string $propertyToTotal, bool $absoluteLatest = false): CountAndTotalResult
    {
        $search->limit = null;
        $select = new MySQLSelect(
            table: self::getTableName(),
            className: static::class,
            selectType: MySQLSelectType::CountAndSum,
            search: $search,
            sumProperty: $propertyToTotal
        );

        try {
            $event = self::startPerformanceEvent('MySQL', $select->query, ['params' => $select->params, 'sumProperty' => $propertyToTotal]);

            $db = DB::getInstance($_ENV['MYSQL_DB'], $absoluteLatest);
            $stmt = $db->prepare($select->query);

            if (MYSQL_DEBUG_MODE) {
                echo $select->query . PHP_EOL;
            }

            if (!$stmt->execute($select->params)) {
                throw new DBException('Failed to execute count query');
            }

            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            $result = new CountAndTotalResult($result['count'], (float)($result['sum'] ?? 0));

            $event?->end();
        } catch (\PDOException $e) {
            throw new DBException($e->getMessage());
        }

        return $result;
    }

    /**
     * @param SearchArguments $search
     * @param bool $absoluteLatest
     * @return static[]
     * @throws DBException
     */
    public static function searchStorage(SearchArguments $search, bool $absoluteLatest = false): array
    {
        $select = new MySQLSelect(
            table: self::getTableName(),
            className: static::class,
            selectType: MySQLSelectType::Objects,
            search: $search
        );

        try {
            $event = self::startPerformanceEvent('MySQL', $select->query, ['params' => $select->params]);

            if (MYSQL_DEBUG_MODE) {
                echo $select->query . PHP_EOL;
            }

            $db = DB::getInstance($_ENV['MYSQL_DB'], $absoluteLatest);
            $stmt = $db->prepare($select->query);

            if (!$stmt->execute($select->params)) {
                throw new DBException('Failed to execute search query');
            }

            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $event?->end();
        } catch (\PDOException $e) {
            throw new DBException($e->getMessage());
        }

        $objects = [];
        foreach ($results as $result) {
            $objects[] = static::getInstance($result);
        }

        return $objects;
    }

    /**
     * eradicate this object
     * @throws DBException
     */
    public function eraseStorage(): void
    {
        $db = DB::getInstance($_ENV['MYSQL_DB'], true);
        $table = self::getTableName();
        $idProp = self::getAutoIncrementProperty();
        $query = "DELETE FROM {$table} WHERE `{$idProp}`=?";
        $event = self::startPerformanceEvent('MySQL', $query, ['params' => [$this->$idProp]]);

        if (MYSQL_DEBUG_MODE) {
            echo $query . PHP_EOL;
        }

        $res = $db->prepare($query)->execute([$this->$idProp]);

        if (!$res) {
            throw new DBException('Failed to execute erase query');
        }

        $event?->end();
    }

    /**
     * @param array $changedProperties
     * @return SaveType
     * @throws DBException
     */
    protected function saveStorage(array $changedProperties = []): SaveType
    {
        $idProp = self::getAutoIncrementProperty();
        return isset($this->$idProp) ?
            $this->saveUpdate($changedProperties) :
            $this->saveInsert();
    }

    /**
     * does the save as a sequel update
     * @param array $changedProperties
     * @return SaveType
     * @throws DBException
     */
    protected function saveUpdate(array $changedProperties): SaveType
    {
        $db = DB::getInstance($_ENV['MYSQL_DB'], true);
        $table = self::getTableName();
        $properties = array_intersect($changedProperties, self::getPersistentProperties());
        $values = [];
        $sets = [];
        foreach ($properties as $prop) {
            $values[] = $this->savePropertyValue($prop, $this->$prop ?? null);
            $sets[] = "`{$prop}` = ?";
        }

        if (empty($sets)) {
            return SaveType::Update;
        }

        $sets = implode(', ', $sets);
        $idProp = self::getAutoIncrementProperty();
        $query = "UPDATE {$table} SET {$sets} WHERE `{$idProp}`=?";
        $params = array_merge($values, [$this->$idProp]);
        $event = self::startPerformanceEvent('MySQL', $query, ['params' => $params, 'changedProperties' => $properties]);

        if (MYSQL_DEBUG_MODE) {
            echo $query . PHP_EOL;
        }

        $stmt = $db->prepare($query);
        $res = $stmt->execute($params);

        if (!$res) {
            throw new DBException('Failed to execute update query');
        }

        $event?->end();

        if ($stmt->rowCount() === 0) {
            // #region agent log
            if (strpos(get_class($this), 'Slate') !== false) {
                $logData = ['sessionId'=>'debug-session','runId'=>'run1','hypothesisId'=>'UPDATE_ZERO_ROWS','location'=>'MySQL.php:saveUpdate','message'=>'UPDATE found 0 rows','data'=>['class'=>get_class($this),'id'=>$this->$idProp??null,'table'=>$table],'timestamp'=>time()*1000];
                file_put_contents('/var/www/html/.cursor/debug.log', json_encode($logData)."\n", FILE_APPEND | LOCK_EX);
            }
            // #endregion
            $stmt = $db->prepare("SELECT * FROM {$table} WHERE `{$idProp}`=?");
            $res = $stmt->execute([$this->$idProp]);

            if (!$res) {
                throw new DBException('After update did not affect any rows, select to see if the row exists failed');
            }

            if (!$stmt->fetch(DB::FETCH_ASSOC)) {
                // #region agent log
                if (strpos(get_class($this), 'Slate') !== false) {
                    $logData = ['sessionId'=>'debug-session','runId'=>'run1','hypothesisId'=>'UPDATE_TO_INSERT','location'=>'MySQL.php:saveUpdate','message'=>'UPDATE found 0 rows, row does not exist, falling back to INSERT','data'=>['class'=>get_class($this),'staleId'=>$this->$idProp??null,'table'=>$table],'timestamp'=>time()*1000];
                    file_put_contents('/var/www/html/.cursor/debug.log', json_encode($logData)."\n", FILE_APPEND | LOCK_EX);
                }
                // #endregion
                return $this->saveInsert(true);
            }
        }

        return SaveType::Update;
    }

    /**
     * does the save as a sql insert
     * @param bool $useSetId
     * @return SaveType
     * @throws DBException
     */
    protected function saveInsert(bool $useSetId = false): SaveType
    {
        $db = DB::getInstance($_ENV['MYSQL_DB'], true);
        $table = self::getTableName();
        $properties = self::getPersistentProperties();
        $placeHolders = implode(',', array_fill(0, count($properties), '?'));
        $values = [];
        $props = [];
        $idProp = self::getAutoIncrementProperty();

        if ($useSetId && isset($this->$idProp)) {
            $values[] = $this->$idProp;
            $placeHolders .= ',?';
            $props[] = "`{$idProp}`";
        }
        foreach ($properties as $prop) {
            $value = $this->savePropertyValue($prop, $this->$prop ?? null);
            $values[] = $value;
            $props[] = '`' . $prop . '`';
        }
        $properties = implode(', ', $props);
        $query = "INSERT INTO {$table} ({$properties}) VALUES ({$placeHolders})";
        $event = self::startPerformanceEvent('MySQL', $query, ['params' => $values]);

        if (MYSQL_DEBUG_MODE) {
            echo $query . PHP_EOL;
        }

        $stmt = $db->prepare($query);
        try {
            $stmt->execute($values);
        } catch (\PDOException $e) {
            throw new DBException(static::class . ': ' . $e->getMessage());
        }

        $event?->end();

        $idBefore = $this->$idProp ?? null;
        if (!$useSetId || !isset($this->$idProp)) {
            $this->$idProp = (int)$db->lastInsertId();
        }
        // #region agent log
        if (strpos(get_class($this), 'Slate') !== false) {
            $logData = ['sessionId'=>'debug-session','runId'=>'run1','hypothesisId'=>'INSERT_COMPLETE','location'=>'MySQL.php:saveInsert','message'=>'INSERT completed','data'=>['class'=>get_class($this),'idBefore'=>$idBefore,'idAfter'=>$this->$idProp,'useSetId'=>$useSetId,'lastInsertId'=>(int)$db->lastInsertId()],'timestamp'=>time()*1000];
            file_put_contents('/var/www/html/.cursor/debug.log', json_encode($logData)."\n", FILE_APPEND | LOCK_EX);
        }
        // #endregion

        return SaveType::Insert;
    }

    /**
     * @throws DBException
     */
    public static function dropTable(): void
    {
        if (!$_ENV['MYSQL_ALLOW_TABLE_MUTATION']) {
            throw new DBException('Table mutation is not allowed in this environment');
        }
        $db = DB::getInstance($_ENV['MYSQL_DB'], true);
        $table = self::getTableName();
        $db->query("DROP TABLE IF EXISTS `{$table}`");
    }

    /**
     * @return void
     * @throws DBException
     */
    public static function createTable(): void
    {
        $db = DB::getInstance($_ENV['MYSQL_DB'], true);
        $db->query(self::getSchema());
    }

    public static function getSchema(): string
    {
        $table = self::getTableName();
        $columnStrings = [];
        $keyStrings = [];
        $primaryKeyPropertyNames = [];
        $indices = [];

        $class = new \ReflectionClass(get_called_class());

        foreach ($class->getProperties() as $property) {

            $attributes = array_merge(
                $property->getAttributes(Impersistent::class),
                $property->getAttributes(ChildrenClass::class),
                $property->getAttributes(ParentObject::class)
            );
            if (count($attributes) === 0 && !$property->isStatic()) {
                $columnStrings[] = self::getPropertySchema($property);
            }

            $primaryKeyAttributes = $property->getAttributes(PrimaryKey::class);
            if (count($primaryKeyAttributes) > 0) {
                $primaryKeyPropertyNames[] = $property->getName();
            }

            $foreignKeyAttributes = $property->getAttributes(ForeignKey::class);
            if (count($foreignKeyAttributes) > 0) {
                $foreignKeyInstance = $foreignKeyAttributes[0]->newInstance();
                if ($foreignKeyInstance->column !== null) {
                    $keyStrings[] = "FOREIGN KEY (`{$property->getName()}`) REFERENCES `{$foreignKeyInstance->table}`(`{$foreignKeyInstance->column}`)  ON DELETE CASCADE";
                }
            }

            $indexAttributes = $property->getAttributes(Index::class);
            if (count($indexAttributes) > 0) {
                $indexInstance = $indexAttributes[0]->newInstance();
                if (!isset($indices[$indexInstance->indexName])) {
                    $indices[$indexInstance->indexName] = [];
                }
                $indices[$indexInstance->indexName][] = $property->getName();
            }
        }

        // add the primary key string to keyStrings, referencing each primaryKeyPropertyName inside ``
        array_unshift($keyStrings, "PRIMARY KEY (" . implode(', ', array_map(fn($name) => "`{$name}`", $primaryKeyPropertyNames)) . ")");
        foreach ($indices as $name => $properties) {
            $keyStrings[] = "INDEX `{$name}` (" . implode(', ', array_map(fn($name) => "`{$name}`", $properties)) . ")";
        }

        return implode(PHP_EOL, [
            'CREATE ' . "TABLE IF NOT EXISTS `{$table}` (",
            implode(', ' . PHP_EOL, array_merge($columnStrings, $keyStrings)),
            ') ENGINE=InnoDB CHARSET=utf8mb4 COMMENT="storage for objects of PHP class ' . get_called_class() . '";'
        ]);
    }

    /**
     * @param \ReflectionProperty $property
     * @return string
     * @throws DBException
     */
    protected static function getPropertySchema(\ReflectionProperty $property): string
    {
        $propertyName = $property->getName();
        $typeName = (string)$property->getType();
        $typeOptions = '';
        $type = '';

        // Check for nullable FIRST, before checking enum type
        if (str_starts_with($typeName, '?')) {
            $typeName = substr($typeName, 1);
            $nullable = true;
        } else {
            $nullable = false;
        }

        // Now check if it's an enum (after stripping ? if nullable)
        if (enum_exists($typeName)) {
            $enum = $typeName;
            $type = 'enum';
            $enumOptions = [];
            foreach ($enum::cases() as $case) {
                $enumOptions[] = "'{$case->value}'";
            }

            $typeOptions = implode(', ', $enumOptions);
        }

        if ($typeName === 'int') {
            $type = 'int';

            // number range
            $numberRangeAttributes = $property->getAttributes(NumberRange::class);
            if (count($numberRangeAttributes) > 0) {
                $constraintInstance = $numberRangeAttributes[0]->newInstance();
                if ($constraintInstance->max) {
                    $max = $constraintInstance->max;
                    if ($max <= 255) {
                        $type = 'tinyint';
                    } else if ($max <= 65535) {
                        $type = 'smallint';
                    } else if ($max <= 16777215) {
                        $type = 'mediumint';
                    } else if ($max <= 4294967295) {
                        $type = 'bigint';
                    }
                }
            }

            // let's see if it has the timestamp attribute
            $timestampAttributes = $property->getAttributes(Timestamp::class);
            if (count($timestampAttributes) > 0) {
                $type = 'bigint';
            }
        } elseif ($typeName === 'bool') {
            $type = 'tinyint';
        } elseif ($typeName === 'float') {
            $type = 'decimal';
            $typeOptions = '11, 2';
        } else if ($typeName === 'string') {
            $strlenAttributes = $property->getAttributes(Strlen::class);
            if (count($strlenAttributes) > 0) {
                $constraintInstance = $strlenAttributes[0]->newInstance();
                $type = 'varchar';
                $typeOptions = $constraintInstance->max;
            } else {
                $type = 'text';
            }
        } else if (strtolower($typeName) === 'datetime') {
            $type = 'datetime';
        } else if ($typeName === 'array') {
            $type = 'text';
        }

        $autoIncrementAttributes = $property->getAttributes(AutoIncrement::class);
        $isAutoIncrement = count($autoIncrementAttributes) > 0;

        if (empty($type)) {
            throw new DBException("Could not determine schema type for property {$propertyName}");
        }

        return "`{$propertyName}` {$type}" . (!empty($typeOptions) ? "({$typeOptions})" : '') . ($nullable ? "" : " NOT NULL") . ($isAutoIncrement ? ' AUTO_INCREMENT' : '');
    }

    private static function getTableNameFromSchema(string $schema): string
    {
        if (preg_match('/CREATE TABLE IF NOT EXISTS `([^`]+)`/', $schema, $matches)) {
            return $matches[1];
        }
        return '';
    }

    private static function getTableDependencies(string $schema): array
    {
        $dependencies = [];
        // Match all FOREIGN KEY references
        if (preg_match_all('/FOREIGN KEY\s*\([^)]+\)\s*REFERENCES\s*`([^`]+)`/', $schema, $matches)) {
            $dependencies = array_unique($matches[1]);
        }
        return $dependencies;
    }

    private static function sortSchemasByDependency(array $tableInfo): array
    {
        $sorted = [];
        $visited = [];
        $visiting = [];

        // Helper function for depth-first topological sort
        $visit = function ($tableName) use (&$visit, &$sorted, &$visited, &$visiting, $tableInfo) {
            // Check for circular dependency
            if (isset($visiting[$tableName])) {
                throw new \Exception("Circular dependency detected involving table: $tableName");
            }

            // Skip if already visited
            if (isset($visited[$tableName])) {
                return;
            }

            $visiting[$tableName] = true;

            // Visit all dependencies first
            foreach ($tableInfo[$tableName]['dependencies'] as $dep) {
                if (isset($tableInfo[$dep])) {
                    $visit($dep);
                }
            }

            unset($visiting[$tableName]);
            $visited[$tableName] = true;
            $sorted[] = $tableInfo[$tableName]['schema'];
        };

        // Visit all tables
        foreach (array_keys($tableInfo) as $tableName) {
            if (!isset($visited[$tableName])) {
                $visit($tableName);
            }
        }

        return $sorted;
    }

    /**
     * Get all schemas in dependency order
     * @return array Ordered array of CREATE TABLE statements
     * @throws \Exception
     */
    public static function getAllSchemas(): array
    {
        $classes = Stack::getClassesInModuleNamespaces('Model', true, MySQL::class);
        $tableInfo = [];

        // First collect all schemas and their dependencies
        foreach ($classes as $class) {
            try {
                $reflection = new \ReflectionClass($class);
                if ($reflection->isAbstract()) {
                    continue;
                }
                try {
                    $schema = $class::getSchema();
                    if ($schema) {
                        $tableName = self::getTableNameFromSchema($schema);
                        if ($tableName) {
                            $tableInfo[$tableName] = [
                                'schema' => $schema,
                                'dependencies' => self::getTableDependencies($schema)
                            ];
                        }
                    }
                } catch (\Exception $e) {
                    continue;
                }
            } catch (\ReflectionException $e) {
                continue;
            }
        }

        return self::sortSchemasByDependency($tableInfo);
    }

    /**
     * Apply all schemas to the database
     * @throws DBException
     */
    public static function applyAllSchemas(): void
    {
        if (!$_ENV['MYSQL_ALLOW_TABLE_MUTATION']) {
            throw new DBException('Table mutation is not allowed in this environment');
        }

        try {
            $schemas = self::getAllSchemas();
            $db = DB::getInstance($_ENV['MYSQL_DB'], true);

            foreach ($schemas as $schema) {
                $db->query($schema);
            }
        } catch (\Exception $e) {
            throw new DBException('Failed to apply schemas: ' . $e->getMessage());
        }
    }
}
