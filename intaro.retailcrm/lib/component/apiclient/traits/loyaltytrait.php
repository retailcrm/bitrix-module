<?php
/**
 * PHP version 7.1
 *
 * @category Integration
 * @package  Intaro\RetailCrm\Component\ApiClient\Traits
 * @author   retailCRM <integration@retailcrm.ru>
 * @license  MIT
 * @link     http://retailcrm.ru
 * @see      http://retailcrm.ru/docs
 */
namespace Intaro\RetailCrm\Component\ApiClient\Traits;

use Intaro\RetailCrm\Component\Json\Deserializer;
use Intaro\RetailCrm\Component\Json\Serializer;
use Intaro\RetailCrm\Model\Api\Request\Loyalty\LoyaltyCalculateRequest;
use Intaro\RetailCrm\Model\Api\Request\Order\Loyalty\OrderLoyaltyApplyRequest;
use Intaro\RetailCrm\Model\Api\Request\SmsVerification\SmsVerificationConfirmRequest;
use Intaro\RetailCrm\Model\Api\Response\Loyalty\LoyaltyCalculateResponse;
use Intaro\RetailCrm\Model\Api\Response\Order\Loyalty\OrderLoyaltyApplyResponse;
use Intaro\RetailCrm\Model\Api\Response\SmsVerification\SmsVerificationConfirmResponse;
use Intaro\RetailCrm\Model\Api\Response\SmsVerification\SmsVerificationStatusRequest;
use Intaro\RetailCrm\Model\Api\Response\SmsVerification\SmsVerificationStatusResponse;

/**
 * Trait LoyaltyTrait
 * @package Intaro\RetailCrm\Component\ApiClient\Traits
 */
trait LoyaltyTrait
{
    /**
     * @param \Intaro\RetailCrm\Model\Api\Request\SmsVerification\SmsVerificationConfirmRequest $request
     * @return \Intaro\RetailCrm\Model\Api\Response\SmsVerification\SmsVerificationConfirmResponse|null
     */
    protected function confirmLpVerificationBySMS(SmsVerificationConfirmRequest $request): ?SmsVerificationConfirmResponse
    {
        $serialized = Serializer::serializeArray($request);
        $response   = $this->client->confirmLpVerificationBySMS($serialized);
        
        return Deserializer::deserializeArray($response->getResponseBody(), SmsVerificationConfirmResponse::class);
    }
    
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
        $response   = $this->client->loyaltyOrderApply($serialized);
        
        return Deserializer::deserializeArray($response->getResponseBody(), LoyaltyCalculateResponse::class);
    }
}
