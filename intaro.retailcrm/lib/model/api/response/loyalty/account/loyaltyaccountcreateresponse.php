<?php
/**
 * PHP version 7.1
 *
 * @category Integration
 * @package  Intaro\RetailCrm\Model\Api\Response\Loyalty\Account
 * @author   retailCRM <integration@retailcrm.ru>
 * @license  MIT
 * @link     http://retailcrm.ru
 * @see      http://retailcrm.ru/docs
 */
namespace Intaro\RetailCrm\Model\Api\Response\Loyalty\Account;

use Intaro\RetailCrm\Model\Api\AbstractApiModel;
use Intaro\RetailCrm\Model\Api\LoyaltyAccount;

/**
 * Class LoyaltyAccountCreateResponse
 *
 * @package Intaro\RetailCrm\Model\Api\Response\Loyalty\Account
 */
class LoyaltyAccountCreateResponse extends AbstractApiModel
{
    /**
     * Результат запроса (успешный/неуспешный)
     *
     * @var boolean $success
     *
     * @Mapping\Type("boolean")
     * @Mapping\SerializedName("success")
     */
    private $success;
    
    /**
     * @var \Intaro\RetailCrm\Model\Api\LoyaltyAccount
     *
     * @Mapping\Type("\Intaro\RetailCrm\Model\Api\LoyaltyAccount")
     * @Mapping\SerializedName("loyalty_account")
     */
    private $loyaltyAccount;
    
    /**
     * @var array $warnings
     *
     * @Mapping\Type("array")
     * @Mapping\SerializedName("warnings")
     */
    private $warnings;
    
    /**
     * @return bool
     */
    public function isSuccess(): bool
    {
        return $this->success;
    }
    
    /**
     * @param bool $success
     */
    public function setSuccess(bool $success): void
    {
        $this->success = $success;
    }
    
    /**
     * @return \Intaro\RetailCrm\Model\Api\LoyaltyAccount
     */
    public function getLoyaltyAccount(): LoyaltyAccount
    {
        return $this->loyaltyAccount;
    }
    
    /**
     * @param \Intaro\RetailCrm\Model\Api\LoyaltyAccount $loyaltyAccount
     */
    public function setLoyaltyAccount(LoyaltyAccount $loyaltyAccount): void
    {
        $this->loyaltyAccount = $loyaltyAccount;
    }
    
    /**
     * @return array
     */
    public function getWarnings(): array
    {
        return $this->warnings;
    }
    
    /**
     * @param array $warnings
     */
    public function setWarnings(array $warnings): void
    {
        $this->warnings = $warnings;
    }
}
