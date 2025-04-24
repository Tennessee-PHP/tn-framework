<?php

namespace TN\TN_S3Download\Model\File;

use TN\TN_Core\Attribute\MySQL\TableName;
use TN\TN_Core\Interface\Persistence;
use TN\TN_Core\Model\PersistentModel\PersistentModel;
use TN\TN_Core\Model\PersistentModel\Storage\MySQL\MySQL;
use TN\TN_Core\Model\Storage\DB;
use TN\TN_Core\Model\Time\Time;

#[TableName('file_download_counts')]
class FileDownloadCount implements Persistence
{
    use MySQL;
    use PersistentModel;

    public string $userIdentifier;
    public string $file;
    public bool $premium;
    public int $ts;

    /**
     * get statistics on download counts today, last 7, last 30, this year
     */
    public static function getStats(): array
    {
        $timestamps = [
            'today' => strtotime(date('Y-m-d 00:00:00', Time::getNow())),
            'week' => Time::getNow() - Time::ONE_WEEK,
            'month' => Time::getNow() - Time::ONE_MONTH,
            'year' => strtotime(date('Y-01-01 00:00:00', Time::getNow()))
        ];

        $allStats = [];
        foreach ($timestamps as $key => $ts) {
            foreach (self::getStatsSince($ts) as $file => $counts) {
                if (!isset($allStats[$file])) {
                    $allStats[$file] = [
                        'file' => $file
                    ];
                }
                $allStats[$file][$key] = $counts;
            }
        }

        return array_values($allStats);
    }

    protected static function getStatsSince(int $ts): array
    {
        $db = DB::getInstance($_ENV['MYSQL_DB']);
        $table = self::getTableName();
        $stats = [];
        $counts = [
            'total' => [
                'count' => '*',
                'where' => ' WHERE `ts` >= ' . $ts
            ],
            'premium' => [
                'count' => 'distinct(`userIdentifier`)',
                'where' => ' WHERE `premium` = 1 AND `ts` >= ' . $ts
            ],
            'free' => [
                'count' => 'distinct(`userIdentifier`)',
                'where' => ' WHERE `premium` = 0 AND `ts` >= ' . $ts
            ]
        ];
        foreach ($counts as $countKey => $info) {
            $count = $info['count'];
            $where = $info['where'];
            $query = "SELECT count({$count}) as `count`, `file` FROM {$table} {$where} GROUP BY `file` ORDER BY `count` DESC";
            foreach ($db->query($query) as $res) {
                if (!isset($stats[$res['file']])) {
                    $stats[$res['file']] = [];
                }
                $stats[$res['file']][$countKey] = $res['count'];
            }
        }

        return $stats;
    }
}
