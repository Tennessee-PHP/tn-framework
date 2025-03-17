<?php

namespace TN\TN_Billing\Trait;
use TN\TN_Billing\Model\Gateway\Gateway;

/**
 * get related gateway
 * 
 */
trait GetGateway
{
    /**
     * returns the associated gateway
     * @return ?Gateway
     */
    public function getGateway(): ?Gateway
    {
        return Gateway::getInstanceByKey($this->gatewayKey);
    }
}