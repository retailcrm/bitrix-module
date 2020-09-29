<?php
/**
 * PHP version 7.1
 *
 * @category Integration
 * @package  Intaro\RetailCrm\Model\Api\Request\Loyalty\Account
 * @author   retailCRM <integration@retailcrm.ru>
 * @license  MIT
 * @link     http://retailcrm.ru
 * @see      http://retailcrm.ru/docs
 */
namespace Intaro\RetailCrm\Model\Api\Request\Loyalty\Account;

use Intaro\RetailCrm\Component\Json\Mapping;
use Intaro\RetailCrm\Model\Api\AbstractApiModel;

/**
 * Class LoyaltyAccountActivateRequest
 *
 * @package Intaro\RetailCrm\Model\Api\Request\Loyalty\Account
 */
class LoyaltyAccountActivateRequest extends AbstractApiModel
{
    /**
     * Id участия в программе лояльности
     *
     * @var integer $loyaltyId
     *
     * @Mapping\Type("integer")
     * @Mapping\SerializedName("id")
     */
    private $loyaltyId;
    
    /**
     * @return int
     */
    public function getLoyaltyId(): int
    {
        return $this->loyaltyId;
    }
    
    /**
     * @param int $loyaltyId
     */
    public function setLoyaltyId(int $loyaltyId): void
    {
        $this->loyaltyId = $loyaltyId;
    }
}
