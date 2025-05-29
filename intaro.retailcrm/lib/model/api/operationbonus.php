<?php

namespace Intaro\RetailCrm\Model\Api\Loyalty;

use Intaro\RetailCrm\Component\Json\Mapping;

/**
 * Class OperationBonus
 */
class OperationBonus
{
    /**
     * Дата активации бонусов
     *
     * @var \DateTime $activationDate
     *
     * @Mapping\Type("DateTime<'Y-m-d H:i:s'>")
     * @Mapping\SerializedName("activationDate")
     */
    public $activationDate;

    /**
     * Дата сгорания бонусов
     *
     * @var \DateTime $expireDate
     *
     * @Mapping\Type("DateTime<'Y-m-d H:i:s'>")
     * @Mapping\SerializedName("expireDate")
     */
    public $expireDate;
}
