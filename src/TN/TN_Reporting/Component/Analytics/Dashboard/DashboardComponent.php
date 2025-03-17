<?php

namespace TN\TN_Reporting\Component\Analytics\Dashboard;

use TN\TN_Core\Attribute\Components\FromRequest;
use TN\TN_Core\Component\HTMLComponent;
use TN\TN_Core\Model\Package\Stack;
use TN\TN_Reporting\Component\Analytics\Dashboard\DashboardSummary\DashboardSummary;
use TN\TN_Reporting\Component\Analytics\Dashboard\TypeReport\TypeReport;

class DashboardComponent extends HTMLComponent {

    public static function getDashboardComponentByKey(string $key): DashboardComponent
    {
        $parts = explode('\\', TypeReport::class);

        // package off the start
        array_shift($parts);

        // actual class name off the end
        array_pop($parts);
        $namespace = implode('\\', $parts);

        foreach(Stack::getClassesInPackageNamespaces($namespace) as $class) {
            try {
                $reflectionClass = new \ReflectionClass($class);
            } catch (\ReflectionException) {
                continue;
            };

            if (!$reflectionClass->isSubClassOf(TypeReport::class)) {
                continue;
            }
            // use a reflection class to make sure it extends this one
            $report = new $class();
            if ($report->reportKey === $key) {
                return $report;
            }
        }
        return new DashboardSummary();
    }
}