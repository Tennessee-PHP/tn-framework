<?php

namespace TN\TN_Billing\Model\Subscription\Plan;
use PDO;
use TN\TN_Billing\Model\Subscription\BillingCycle\BillingCycle;
use TN\TN_Billing\Model\VoucherCode;
use TN\TN_Billing\Trait\GetBillingCycle;
use TN\TN_Core\Attribute\MySQL\AutoIncrement;
use TN\TN_Core\Attribute\MySQL\PrimaryKey;
use TN\TN_Core\Attribute\MySQL\TableName;
use TN\TN_Core\Model\Package\Stack;
use TN\TN_Core\Model\PersistentModel\PersistentModel;
use TN\TN_Core\Model\PersistentModel\Search\SearchArguments;
use TN\TN_Core\Model\PersistentModel\Search\SearchComparison;
use TN\TN_Core\Model\PersistentModel\Storage\MySQL\MySQL;
use TN\TN_Core\Model\Storage\DB;

/**
 * a price for a plan
 *
 * @property-read float $monthlyPrice
 */
#[TableName('plan_prices')]
class Price
{
    use MySQL;
    use PersistentModel;
    use GetBillingCycle;

    public string $planKey;
    public string $billingCycleKey;
    public float $price;

    /**
     * magic getter
     *
     * @param string $prop
     * @return void
     */
    public function __get(string $prop): mixed
    {
        switch ($prop) {
            case 'monthlyPrice':
                return $this->getMonthlyPrice();
            default:
            return property_exists($this, $prop) ? $this->$prop : null;
        }
    }

    /** @return float the monthly price */
    protected function getMonthlyPrice(): float
    {
        return round($this->price / BillingCycle::getInstanceByKey($this->billingCycleKey)->numMonths, 2);
    }

    /**
     * get the price for a plan/billing cycle (or create it!)
     * @param string $planKey
     * @param string $billingCycleKey
     * @return ?Price
     */
    public static function readFromKeys(string $planKey, string $billingCycleKey): ?Price
    {
        return self::searchOne(new SearchArguments([
            new SearchComparison('`planKey`', '=', $planKey),
            new SearchComparison('`billingCycleKey`', '=', $billingCycleKey)
        ]));
    }

    /**
     * apply a voucher code to this price (if it can be applied!!)
     * @param mixed|false $code
     * @return float
     */
    public function applyVoucherCode(mixed $code = false): float
    {
        if (!($code instanceof VoucherCode) || !$code->canApplyToPlan(Plan::getInstanceByKey($this->planKey))) {
            return $this->price;
        }

        return $this->price * ($code->discountPercentage / 100);

    }
}