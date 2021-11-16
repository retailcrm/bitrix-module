<?php

/**
 * PHP version 7.1
 *
 * @category Integration
 * @package  Intaro\RetailCrm\Model\Api
 * @author   RetailCRM <integration@retailcrm.ru>
 * @license  MIT
 * @link     http://retailcrm.ru
 * @see      http://retailcrm.ru/docs
 */

namespace Intaro\RetailCrm\Model\Api;

use Intaro\RetailCrm\Component\Json\Mapping;

/**
 * Class CustomerNote
 *
 * @package Intaro\RetailCrm\Model\Api
 */
class CustomerNote extends AbstractApiModel
{
    /**
     * @var integer $id
     *
     * @Mapping\Type("integer")
     * @Mapping\SerializedName("id")
     */
    public $id;

    /**
     * @var string $externalId
     *
     * @Mapping\Type("string")
     * @Mapping\SerializedName("text")
     */
    public $text;

    /**
     * Дата создания в системе
     *
     * @var \DateTime $createdAt
     *
     * @Mapping\Type("DateTime<'Y-m-d H:i:s'>")
     * @Mapping\SerializedName("createdAt")
     */
    public $createdAt;

    /**
     * ID менеджера, к которому привязан клиент
     *
     * @var integer $managerId
     *
     * @Mapping\Type("integer")
     * @Mapping\SerializedName("managerId")
     */
    public $managerId;

    /**
     * @var \Intaro\RetailCrm\Model\Api\Customer
     *
     * @Mapping\Type("Intaro\RetailCrm\Model\Api\Customer")
     * @Mapping\SerializedName("customer")
     */
    public $customer;
}
