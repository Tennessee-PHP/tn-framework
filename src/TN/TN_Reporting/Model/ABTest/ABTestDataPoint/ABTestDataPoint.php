<?php

namespace TN\TN_Reporting\Model\ABTest\ABTestDataPoint;

use PDO;
use TN\TN_Core\Attribute\MySQL\TableName;
use TN\TN_Core\Error\ValidationException;
use TN\TN_Core\Interface\Persistence;
use TN\TN_Core\Model\IP\IP;
use TN\TN_Core\Model\Package\Stack;
use TN\TN_Core\Model\PersistentModel\PersistentModel;
use TN\TN_Core\Model\PersistentModel\Search\SearchArguments;
use TN\TN_Core\Model\PersistentModel\Search\SearchComparison;
use TN\TN_Core\Model\PersistentModel\Storage\MySQL\MySQL;
use TN\TN_Core\Model\Storage\DB;
use TN\TN_Core\Model\Time\Time;
use TN\TN_Reporting\Model\TrackedVisitor\TrackedVisitor;

#[TableName('abtest_data_points')]
class ABTestDataPoint implements Persistence
{
    use MySQL;
    use PersistentModel;

    const int TIME_TO_SUCCEED = Time::ONE_HOUR;

    public string $abTestKey;
    public string $variantTemplate;
    public int $trackedVisitorId;
    public int $viewedTs;
    public string $ip;
    public bool $success = false;

    /**
     * get an instance
     * @param string $abTestKey
     * @param string $variantTemplate
     * @param bool $create
     * @return ABTestDataPoint|null
     * @throws ValidationException
     */
    public static function getInstanceFromKey(string $abTestKey, string $variantTemplate, bool $create = true): ?ABTestDataPoint
    {
        $trackedVisitor = TrackedVisitor::getInstance();

        // viewedTs must be within self::TIME_TO_SUCCEED
        $abTestDataPoint = static::searchOne(new SearchArguments([
            new SearchComparison('`abTestKey`', '=', $abTestKey),
            new SearchComparison('`trackedVisitorId`', '=', $trackedVisitor->id),
            new SearchComparison('`variantTemplate`', '=', $variantTemplate),
            new SearchComparison('`viewedTs`', '>', Time::getNow() - self::TIME_TO_SUCCEED)
        ]));

        if ($abTestDataPoint) {
            return $abTestDataPoint;
        }

        if (!$create) {
            return null;
        }

        static::batchErase(static::search(new SearchArguments([
            new SearchComparison('`ip`', '=', IP::getAddress()),
            new SearchComparison('`abTestKey`', '=', $abTestKey),
            new SearchComparison('`viewedTs`', '>', Time::getNow() - self::TIME_TO_SUCCEED)
        ])));

        // we need to create it!
        $dataPoint = ABTestDataPoint::getInstance();
        $dataPoint->update([
            'abTestKey' => $abTestKey,
            'variantTemplate' => $variantTemplate,
            'trackedVisitorId' => $trackedVisitor->id,
            'ip' => IP::getAddress(),
            'viewedTs' => Time::getNow()
        ]);

        return $dataPoint;
    }

    /**
     * get all the data!
     * @return array
     */
    public static function getAllData(): array
    {
        $table = self::getTableName();
        $db = DB::getInstance($_ENV['MYSQL_DB']);
        $rows = $db->query("
            SELECT count(*) as count, success, variantTemplate, abTestKey
            FROM {$table}
            GROUP BY abTestKey, variantTemplate, success
            ")->fetchAll(PDO::FETCH_ASSOC);
        $tests = [];
        foreach ($rows as $row) {
            $testKey = $row['abTestKey'];
            $variant = $row['variantTemplate'];
            if (!isset($tests[$testKey])) {
                $tests[$testKey] = [];
            }
            if (!isset($tests[$testKey][$variant])) {
                $tests[$testKey][$variant] = [
                    'success' => 0,
                    'total' => 0
                ];
            }
            $tests[$testKey][$variant]['total'] += $row['count'];
            if ($row['success']) {
                $tests[$testKey][$variant]['success'] += $row['count'];
            }
        }
        return $tests;
    }

    /**
     * the tracked visitor has just seen the variant for the test
     * @return void
     * @throws ValidationException
     */
    public function registerView(): void
    {
        // if it's already this value, then thankfully validation will just do nothing!
        $this->update([
            'viewedTs' => Time::getNow()
        ]);
    }

    /**
     * the tracked user hit the success route post-test
     * @return void
     * @throws ValidationException
     */
    public function registerSuccess(): void
    {
        // yay!
        $this->update([
            'success' => true
        ]);
    }
}