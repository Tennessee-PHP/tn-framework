<?php

namespace TN\TN_Billing\Model\Subscription\Content;
use TN\TN_Billing\Model\Subscription\Plan\Plan;

/**
 * content controlled by level
 *
 */
abstract class Content
{
    use \TN\TN_Core\Trait\Getter;
    use \TN\TN_Core\Trait\ExtendedSingletons;

    /** @var string non-db identifier */
    protected string $key;

    /** @var int the required level to view this content */
    protected int $level;

    /** @var string the name of the content (user-facing) */
    protected string $name;

    /** @return Plan|false get the required plan for this content */
    public function getRequiredPlan(): ?Plan
    {
        return Plan::getPlanForLevel($this->level);
    }
}