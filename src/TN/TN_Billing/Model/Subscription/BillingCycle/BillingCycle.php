<?php

namespace TN\TN_Billing\Model\Subscription\BillingCycle;

use TN\TN_Core\Model\Time\Time;

/**
 * BillingCycle is an Abstract class that is extended by Annually/Monthly. It uses two method, one that shows timestamp
 * for next billing, and another that shows the timestamp for previous billing. Never bills after 28th of the month,
 * gets pushed to 1st of following month in order to make sure not to bill on nonexistent day.
 *
 * Every subscription has a billing cycle saved onto it
 *
 * @see \TN\TN_Billing\Model\Subscription\Subscription
 *
 * @property-read string $key
 * @property-read string $name
 * @property-read bool $enabled
 * @property-read int $numMonths
 * @property-read int $numDaysGracePeriod
 */
abstract class BillingCycle
{
    use \TN\TN_Core\Trait\Getter;
    use \TN\TN_Core\Trait\ExtendedSingletons;

    /** @var string a key to be used as a foreign id */
    protected string $key;

    /** @var string the human-readable name of this billing cycle e.g. "Monthly" */
    protected string $name;

    /** @var bool whether the billing cycle is enabled and in use for this site */
    protected bool $enabled = true;

    /** @var int how many months */
    protected int $numMonths;

    /** @var int grace period during which they can still pay us after a payment failure, during which they continue
     * to have access to the site */
    protected int $numDaysGracePeriod = 7;

    /** @var int send out notices to customers within this many days of an upcoming renewal */
    protected int $notifyUpcomingTransactionWithinDays = 7;

    /**
     * get the timestamp of the next debit due for this billing cycle
     * @param int $ts
     * @return int
     */
    public abstract function getNextTs(int $ts): int;

    /**
     * get the timestamp of the next debit due for this billing cycle
     * @param int $ts
     * @return int
     */
    public abstract function getPreviousTs(int $ts): int;


    /** @return array get all enabled billing cycles */
    public static function getEnabledInstances(): array
    {
        $instances = self::getInstances();
        $enableds = [];
        $nextTs = [];
        foreach ($instances as $instance) {
            if ($instance->enabled) {
                $enableds[] = $instance;
                $nextTs[] = $instance->getNextTs(Time::getNow());
            }
        }
        array_multisort($nextTs, SORT_DESC, $enableds);
        return $enableds;
    }

    /**
     * add months to a timestamp; return a new midnight-based timestamp
     * @param int $ts
     * @param int $months
     * @return int
     */
    protected function addMonths(int $ts, int $months): int
    {
        // if the day is 29, 30 or 31, first roll it to the first of the next month
        if ((int)date('j', $ts) >= 29) {
            $month = (int)date('n', $ts) + 1;
            $day = 1;
            $year = (int)date('Y', $ts);
            if ($month > 12) {
                $month = 1;
                $year += 1;
            }
            $ts = strtotime($year . '-' . $month . '-' . $day);
        }

        // now simply add the number of months to it (remember - if current month is 12, increment year, month to 1!)
        $month = (int)date('n', $ts) + $months;
        $year = (int)date('Y', $ts);
        $day = (int)date('j', $ts);

        while ($month > 12) {
            $year += 1;
            $month -= 12;
        }

        return strtotime($year . '-' . $month . '-' . $day);
    }


    /**
     * remove months from a timestamp; return a new midnight-based timestamp
     * @param int $ts
     * @param int $months
     * @return int
     */
    protected function removeMonths(int $ts, int $months): int
    {
        if ($ts === 0) {
            return 0;
        }
        // if the day is 29, 30 or 31, first roll it to the first of the next month
        if ((int)date('j', $ts) >= 29) {
            $ts = $this->removeMonths(strtotime(date('Y', $ts) . '-' . ((int)date('m', $ts) + 1) . '-' . '01'), 1);
        }

        // now simply add the number of months to it (remember - if current month is 12, increment year, month to 1!)
        $month = (int)date('n', $ts) - $months;
        $year = (int)date('Y', $ts);
        $day = (int)date('j', $ts);
        while ($month < 1) {
            $year -= 1;
            $month += 12;
        }
        return strtotime($year . '-' . $month . '-' . $day);
    }
}