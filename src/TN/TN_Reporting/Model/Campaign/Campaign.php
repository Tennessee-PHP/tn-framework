<?php

namespace TN\TN_Reporting\Model\Campaign;

use TN\TN_Billing\Model\VoucherCode;
use TN\TN_Core\Attribute\MySQL\TableName;
use TN\TN_Core\Error\ValidationException;
use TN\TN_Core\Interface\Persistence;
use TN\TN_Core\Model\Package\Stack;
use TN\TN_Core\Model\PersistentModel\PersistentModel;
use TN\TN_Core\Model\PersistentModel\Search\SearchArguments;
use TN\TN_Core\Model\PersistentModel\Search\SearchComparison;
use TN\TN_Core\Model\PersistentModel\Storage\MySQL\MySQL;
use TN\TN_Reporting\Model\Funnel\Funnel;

/**
 * A marketing campaigns that tracks users progress through a funnel and records sales and click metrics for each
 *
 * @see \TN\TN_Billing\Model\VoucherCode
 * 
 */
#[TableName('campaigns')]
class Campaign implements Persistence
{
    use MySQL;
    use PersistentModel;

    const string routePath = 'welcome';
    public bool $useBaseUrl = false;
    public string $key;
    public string $funnelKey;
    public int $funnelEntryStage = 0;
    public int $voucherCodeId = 0;
    public int $affiliateUserId = 0;
    public string $notes = '';

    public function __get(string $property): mixed
    {
        switch ($property) {
            case 'funnel':
                return Funnel::getInstanceByKey($this->funnelKey);
            case 'voucherCode':
                return VoucherCode::readFromId($this->voucherCodeId);
        }
        return null;
    }

    public static function readFromKey($key): ?Campaign
    {
        return static::searchOne(new SearchArguments(new SearchComparison('`key`', '=', $key)));
    }

    /**
     * get a campaign from a key
     * @param $key
     * @return ?Campaign
     * @deprecated
     */
    public static function getCampaignFromKey($key): ?Campaign
    {
        return self::readFromKey($key);
    }

    /**
     * @return void add custom validations for a voucher code
     * @throws ValidationException
     */
    protected function customValidate(): void
    {
        $errors = [];

        // if the id is not set: key already exists?
        $keyMatches = self::searchByProperty('key', $this->key);
        if (count($keyMatches) > (isset($this->id) ? 1 : 0)) {
            $errors[] = 'A campaigns with this key already exists';
        }

        // make sure funnel exists
        if (!(Funnel::getInstanceByKey($this->funnelKey) instanceof Funnel)) {
            $errors[] = 'No valid funnel selected';
        }

        // if trying to use base url, make sure it's free
        if ($this->useBaseUrl) {
            // todo: need to refactor this way of checking if paths are available
            /*$router = Router::getInstance();
            if (!$router->pathIsAvailable($this->key)) {
                $errors[] = 'The URL ' . $this->getUrl() . ' is already in use. Try a different key for this campaigns';
            }*/
        }

        // voucher code - check start/end dates
        if ($this->voucherCodeId > 0) {
            $voucherCode = VoucherCode::readFromId($this->voucherCodeId);
            if (!($voucherCode instanceof VoucherCode)) {
                $errors[] = 'The promo code selected no longer exists';
            }
        }

        if (!empty($errors)) {
            throw new \TN\TN_Core\Error\ValidationException($errors);
        }
    }

    /** @return string get the url to use for this campaigns */
    public function getUrl(): string
    {
        return $_ENV['BASE_URL'] . (!$this->useBaseUrl ? (self::routePath . '/') : '') . urlencode($this->key);
    }

}