<?php

namespace TN\TN_Reporting\Model\ABTest\ABTestReport;

use TN\TN_Reporting\Model\ABTest\ABTest\ABTest;
use TN\TN_Reporting\Model\ABTest\ABTestDataPoint\ABTestDataPoint;

class ABTestReport
{
    /** @var ABTest[] the tests! */
    public array $tests = [];

    public static function getInstance(): ABTestReport
    {
        $data = ABTestDataPoint::getAllData();

        // set it onto ABTest and variants
        $report = new self();
        foreach ($data as $testKey => $variants) {
            $test = ABTest::getInstanceByKey($testKey);
            if (!$test || !$test->active || $test->settled) {
                continue;
            }
            foreach ($variants as $variantTemplate => $data) {
                $variant = $test->getVariantByTemplate($variantTemplate);
                if ($variant) {
                    $variant->setData($data['total'], $data['success']);
                }
            }
            $test->analyzeData();
            $report->tests[] = $test;
        }

        return $report;
    }
}