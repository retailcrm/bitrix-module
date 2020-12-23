<?php
/**
 * PHP version 7.1
 *
 * @category Integration
 * @package  Intaro\RetailCrm\Component\ApiClient\Traits
 * @author   RetailCRM <integration@retailcrm.ru>
 * @license  MIT
 * @link     http://retailcrm.ru
 * @see      http://retailcrm.ru/docs
 */
namespace Intaro\RetailCrm\Component\ApiClient\Traits;

use Intaro\RetailCrm\Component\Json\Deserializer;
use Intaro\RetailCrm\Component\Json\Serializer;
use Intaro\RetailCrm\Model\Api\Request\Loyalty\Account\LoyaltyAccountActivateRequest;
use Intaro\RetailCrm\Model\Api\Request\Loyalty\Account\LoyaltyAccountCreateRequest;
use Intaro\RetailCrm\Model\Api\Request\Loyalty\Account\LoyaltyAccountRequest;
use Intaro\RetailCrm\Model\Api\Response\Loyalty\Account\LoyaltyAccountResponse;
use Intaro\RetailCrm\Model\Api\Request\Loyalty\LoyaltyCalculateRequest;
use Intaro\RetailCrm\Model\Api\Request\Order\Loyalty\OrderLoyaltyApplyRequest;
use Intaro\RetailCrm\Model\Api\Request\SmsVerification\SmsVerificationConfirmRequest;
use Intaro\RetailCrm\Model\Api\Response\Loyalty\Account\LoyaltyAccountActivateResponse;
use Intaro\RetailCrm\Model\Api\Response\Loyalty\Account\LoyaltyAccountCreateResponse;
use Intaro\RetailCrm\Model\Api\Response\Loyalty\LoyaltyCalculateResponse;
use Intaro\RetailCrm\Model\Api\Response\Order\Loyalty\OrderLoyaltyApplyResponse;
use Intaro\RetailCrm\Model\Api\Response\SmsVerification\SmsVerificationConfirmResponse;
use Intaro\RetailCrm\Model\Api\Response\SmsVerification\SmsVerificationStatusRequest;
use Intaro\RetailCrm\Model\Api\Response\SmsVerification\SmsVerificationStatusResponse;
use RetailCrm\Response\ApiResponse;

/**
 * Trait LoyaltyTrait
 * @package Intaro\RetailCrm\Component\ApiClient\Traits
 */
trait LoyaltyTrait
{
    /**
     * @param \Intaro\RetailCrm\Model\Api\Response\SmsVerification\SmsVerificationStatusRequest $request
     * @return \Intaro\RetailCrm\Model\Api\Response\SmsVerification\SmsVerificationStatusResponse|null
     */
    protected function checkStatusPlVerification(SmsVerificationStatusRequest $request): ?SmsVerificationStatusResponse
    {
        $serialized = Serializer::serializeArray($request);
        $response   = $this->client->checkStatusPlVerification($serialized, $request->checkId);
        
        return Deserializer::deserializeArray($response->getResponseBody(), SmsVerificationStatusResponse::class);
    }
    
    /**
     * @param \Intaro\RetailCrm\Model\Api\Request\Order\Loyalty\OrderLoyaltyApplyRequest $request
     * @return mixed
     */
    public function loyaltyOrderApply(OrderLoyaltyApplyRequest $request): ?OrderLoyaltyApplyResponse
    {
        $serialized = Serializer::serializeArray($request);
        $response   = $this->client->loyaltyOrderApply($serialized);
        
        return Deserializer::deserializeArray($response->getResponseBody(), OrderLoyaltyApplyResponse::class);
    }
    
    /**
     * @param \Intaro\RetailCrm\Model\Api\Request\Loyalty\LoyaltyCalculateRequest $request
     * @return mixed
     */
    public function loyaltyCalculate(LoyaltyCalculateRequest $request): ?LoyaltyCalculateResponse
    {
        $serialized = Serializer::serializeArray($request);
        $response   = $this->client->loyaltyOrderCalculate($serialized);
    
        return Deserializer::deserializeArray($response->getResponseBody(), LoyaltyCalculateResponse::class);
    }
    
    /**
     * @param \Intaro\RetailCrm\Model\Api\Request\Loyalty\Account\LoyaltyAccountCreateRequest $request
     * @return \Intaro\RetailCrm\Model\Api\Response\Loyalty\Account\LoyaltyAccountCreateResponse|null
     */
    public function createLoyaltyAccount(LoyaltyAccountCreateRequest $request): ?LoyaltyAccountCreateResponse
    {
        $serialized = Serializer::serializeArray($request);
        /** @var ApiResponse $response */
        $response   = $this->client->createLoyaltyAccount($serialized);

        return Deserializer::deserializeArray($response->getResponseBody(), LoyaltyAccountCreateResponse::class);
    }
    
    /**
     * @param \Intaro\RetailCrm\Model\Api\Request\Loyalty\Account\LoyaltyAccountActivateRequest $request
     * @return \Intaro\RetailCrm\Model\Api\Response\Loyalty\Account\LoyaltyAccountActivateResponse|null
     */
    public function activateLoyaltyAccount(LoyaltyAccountActivateRequest $request): ?LoyaltyAccountActivateResponse
    {
        $serialized = Serializer::serializeArray($request);
        $response   = $this->client->activateLoyaltyAccount($serialized['id']);

        return Deserializer::deserializeArray($response->getResponseBody(), LoyaltyAccountActivateResponse::class);
    }
    
    /**
     * @param \Intaro\RetailCrm\Model\Api\Request\SmsVerification\SmsVerificationConfirmRequest $request
     * @return \Intaro\RetailCrm\Model\Api\Response\Loyalty\Account\LoyaltyAccountActivateResponse|null
     */
    public function sendVerificationCode(SmsVerificationConfirmRequest $request): ?SmsVerificationConfirmResponse
    {
        $serialized = Serializer::serializeArray($request);
        $response   = $this->client->sendVerificationCode($serialized);

        return Deserializer::deserializeArray($response->getResponseBody(), SmsVerificationConfirmResponse::class);
    }
    
    
    /**
     * @param \Intaro\RetailCrm\Model\Api\Request\Loyalty\Account\LoyaltyAccountRequest $request
     * @return \Intaro\RetailCrm\Model\Api\Request\Loyalty\Account\LoyaltyAccountResponse|null
     */
    public function getLoyaltyAccounts(LoyaltyAccountRequest $request): ?LoyaltyAccountResponse
    {
        $serialized = Serializer::serializeArray($request);
        $response   = $this->client->getLoyaltyAccounts($serialized);

        return Deserializer::deserializeArray($response->getResponseBody(), LoyaltyAccountResponse::class);
    }
}
