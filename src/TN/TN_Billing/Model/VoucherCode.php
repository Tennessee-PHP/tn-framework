<?php

namespace TN\TN_Billing\Model;

use PDO;
use TN\TN_Billing\Model\Subscription\Plan\Plan;
use TN\TN_Core\Attribute\Constraints\NumberRange;
use TN\TN_Core\Attribute\Constraints\OnlyContains;
use TN\TN_Core\Attribute\Constraints\Strlen;
use TN\TN_Core\Attribute\MySQL\TableName;
use TN\TN_Core\Error\ValidationException;
use TN\TN_Core\Interface\Persistence;
use TN\TN_Core\Model\Package\Stack;
use TN\TN_Core\Model\PersistentModel\PersistentModel;
use TN\TN_Core\Model\PersistentModel\Search\SearchArguments;
use TN\TN_Core\Model\PersistentModel\Search\SearchComparison;
use TN\TN_Core\Model\PersistentModel\Storage\MySQL\MySQL;
use TN\TN_Core\Model\Storage\DB;
use TN\TN_Core\Model\Time\Time;

/**
 * allows a user a discount to purchases. Campaigns may be automatically linked to voucher codes so users access these
 * codes from the campaign links.
 *
 * @see \TN\TN_Reporting\Model\Campaign\Campaign
 *
 */
#[TableName('voucher_codes')]
class VoucherCode implements Persistence
{
    use MySQL;
    use PersistentModel;

    /** @var string the readable name of the voucher code */
    #[Strlen(1, 100)]
    public string $name;

    /** @var int the number of transactions to apply the voucher code to, in the case of recurring subscriptions. 0 = forever */
    public int $numTransactions = 1;

    /** @var string the code that users need to enter */
    #[Strlen(3, 50)]
    #[OnlyContains('A-Z0-9\._-', 'uppercase letters, numbers, periods, underscores and dashes')]
    public string $code;

    /** @var int from when it is active */
    public int $startTs;

    /** @var int until when it can be used */
    public int $endTs;

    /** @var int discount as a percentage */
    #[NumberRange(1, 99)]
    public int $discountPercentage;

    /** @var string comma separated list of plan keys */
    public string $planKeys;

    /**
     * count usages of the voucher codes
     * @param $startTs
     * @param $endTs
     * @return array
     */
    public static function countUsages($startTs, $endTs): array
    {
        $db = DB::getInstance($_ENV['MYSQL_DB']);
        $query = "
            SELECT COUNT(*) as uses, SUM((bt.`discount` + bt.`amount`)*(v.`discountPercentage`/100)) as `discount`, v.`code` as code
            FROM `braintree_transactions` as bt, `voucher_codes` as v
            WHERE bt.`ts` > ? AND bt.`ts` < ?
            AND bt.`voucherCodeId` = v.id
            AND bt.`success` = ?
            GROUP BY v.`code`";
        $params = [$startTs, $endTs, 1];
        $stmt = $db->prepare($query);
        $stmt->execute($params);
        $res = [
            'uses' => 0,
            'discountTotal' => 0,
            'codes' => []
        ];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $res['uses'] += $row['uses'];
            $row['discount'] = round($row['discount'], 2);
            $res['discountTotal'] += $row['discount'];
            $res['codes'][$row['code']] = [
                'uses' => $row['uses'],
                'discountTotal' => $row['discount']
            ];
        }
        return $res;
    }

    /**
     * @return void add custom validations for a voucher code
     * @throws ValidationException
     */
    protected function customValidate(): void
    {
        $errors = [];

        // if the id is not set: are the username and email already existing?
        $codeMatches = self::searchByProperty('code', $this->code);
        if (count($codeMatches) > (isset($this->id) ? 1 : 0)) {
            $errors[] = 'A promo code with this code already exists';
        }

        if (count($errors)) {
            throw new ValidationException($errors);
        }
    }

    /**
     * @param string $code
     * @return VoucherCode|null
     */
    public static function getActiveFromCode(string $code): ?VoucherCode
    {
        return static::searchOne(new SearchArguments([
            new SearchComparison('`code`', '=', $code),
            new SearchComparison('`startTs`', '<', Time::getNow()),
            new SearchComparison('`endTs`', '>', Time::getNow())
        ]));
    }

    /**
     * can the code currently be applied to the plan?
     * @param Plan $plan
     * @return bool
     */
    public function canApplyToPlan(Plan $plan): bool
    {
        return ($this->endTs === 0 || $this->endTs > Time::getNow()) &&
            $this->startTs < Time::getNow() &&
            in_array($plan, $this->getPlans());
    }

    /**
     * apply the voucher code to a price
     * @param float $price
     * @return float
     */
    public function applyToPrice(float $price): float
    {
        return $price * ((100 - $this->discountPercentage) / 100);
    }

    /** @return array get all plans for this voucher */
    public function getPlans(): array
    {
        $plans = [];
        foreach (explode(',', $this->planKeys) as $key) {
            $plan = Plan::getInstanceByKey($key);
            if ($plan instanceof Plan) {
                $plans[] = $plan;
            }
        }
        return $plans;
    }



}