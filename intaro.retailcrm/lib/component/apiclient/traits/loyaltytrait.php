<?php

/**
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
use Intaro\RetailCrm\Model\Api\Request\Loyalty\Account\LoyaltyAccountEditRequest;
use Intaro\RetailCrm\Model\Api\Request\Loyalty\Account\LoyaltyAccountRequest;
use Intaro\RetailCrm\Model\Api\Response\Loyalty\Account\LoyaltyAccountEditResponse;
use Intaro\RetailCrm\Model\Api\Response\Loyalty\Account\LoyaltyAccountGetResponse;
use Intaro\RetailCrm\Model\Api\Response\Loyalty\Account\LoyaltyAccountOperationsResponse;
use Intaro\RetailCrm\Model\Api\Response\Loyalty\Account\LoyaltyAccountsResponse;
use Intaro\RetailCrm\Model\Api\Request\Loyalty\LoyaltyCalculateRequest;
use Intaro\RetailCrm\Model\Api\Request\Order\Loyalty\OrderLoyaltyApplyRequest;
use Intaro\RetailCrm\Model\Api\Request\SmsVerification\SmsVerificationConfirmRequest;
use Intaro\RetailCrm\Model\Api\Response\Loyalty\Account\LoyaltyAccountActivateResponse;
use Intaro\RetailCrm\Model\Api\Response\Loyalty\Account\LoyaltyAccountCreateResponse;
use Intaro\RetailCrm\Model\Api\Response\Loyalty\LoyaltyCalculateResponse;
use Intaro\RetailCrm\Model\Api\Response\Loyalty\LoyaltyLoyaltiesResponse;
use Intaro\RetailCrm\Model\Api\Response\Loyalty\LoyaltyLoyaltyResponse;
use Intaro\RetailCrm\Model\Api\Response\Order\Loyalty\OrderLoyaltyApplyResponse;
use Intaro\RetailCrm\Model\Api\Response\SmsVerification\SmsVerificationConfirmResponse;
use Intaro\RetailCrm\Model\Api\Response\SmsVerification\SmsVerificationStatusRequest;
use Intaro\RetailCrm\Model\Api\Response\SmsVerification\SmsVerificationStatusResponse;
use ReflectionException;
use RetailCrm\Response\ApiResponse;

/**
 * Trait LoyaltyTrait
 * @package Intaro\RetailCrm\Component\ApiClient\Traits
 */
trait LoyaltyTrait
{
    /**
     * @param SmsVerificationStatusRequest $request
     *
     * @return SmsVerificationStatusResponse|null
     * @throws ReflectionException
     */
    public function checkStatusPlVerification(SmsVerificationStatusRequest $request): ?SmsVerificationStatusResponse
    {
        $serialized = Serializer::serializeArray($request);
        $response   = $this->client->checkStatusPlVerification($serialized, $request->checkId);

        return Deserializer::deserializeArray($response->getResponseBody(), SmsVerificationStatusResponse::class);
    }

    /**
     * @param OrderLoyaltyApplyRequest $request
     *
     * @return OrderLoyaltyApplyResponse|null
     * @throws ReflectionException
     */
    public function loyaltyOrderApply(OrderLoyaltyApplyRequest $request): ?OrderLoyaltyApplyResponse
    {
        $serialized = Serializer::serializeArray($request);
        $response   = $this->client->loyaltyOrderApply($serialized);

        return Deserializer::deserializeArray($response->getResponseBody(), OrderLoyaltyApplyResponse::class);
    }

    /**
     * @param LoyaltyCalculateRequest $request
     *
     * @return LoyaltyCalculateResponse|null
     * @throws ReflectionException
     */
    public function loyaltyCalculate(LoyaltyCalculateRequest $request): ?LoyaltyCalculateResponse
    {
        $serialized = Serializer::serializeArray($request);
        $response   = $this->client->loyaltyOrderCalculate($serialized);

        return Deserializer::deserializeArray($response->getResponseBody(), LoyaltyCalculateResponse::class);
    }

    /**
     * @param int $loyaltyAccountId
     *
     * @return LoyaltyAccountGetResponse|null
     */
    public function getLoyaltyAccount(int $loyaltyAccountId): ?LoyaltyAccountGetResponse
    {
        $response = $this->client->getLoyaltyAccount($loyaltyAccountId);

        return Deserializer::deserializeArray($response->getResponseBody(), LoyaltyAccountGetResponse::class);
    }

    /**
     * @param LoyaltyAccountRequest $request
     *
     * @return LoyaltyAccountsResponse|null
     * @throws ReflectionException
     */
    public function getLoyaltyAccounts(LoyaltyAccountRequest $request): ?LoyaltyAccountsResponse
    {
        $serialized = Serializer::serializeArray($request);
        $response = $this->client->getLoyaltyAccounts($serialized);

        return Deserializer::deserializeArray($response->getResponseBody(), LoyaltyAccountsResponse::class);
    }

    /**
     * @param int $loyaltyAccountId
     *
     * @return LoyaltyAccountsResponse|null
     */
    public function getLoyaltyAccountOperations(int $loyaltyAccountId): ?LoyaltyAccountOperationsResponse
    {
        $response = $this->client->getLoyaltyAccountOperations($loyaltyAccountId);

        return Deserializer::deserializeArray($response->getResponseBody(), LoyaltyAccountOperationsResponse::class);
    }

    public function getLoyaltyBonesActivationAndBurnInfo(int $loyaltyAccountId, string $status): ApiResponse
    {
        return $this->client->getLoyaltyBonsesActivationAndBurnInfo($loyaltyAccountId, $status);
    }

    /**
     * @param LoyaltyAccountCreateRequest $request
     *
     * @return LoyaltyAccountCreateResponse|null
     * @throws ReflectionException
     */
    public function createLoyaltyAccount(LoyaltyAccountCreateRequest $request): ?LoyaltyAccountCreateResponse
    {
        $serialized = Serializer::serializeArray($request);
        $response   = $this->client->createLoyaltyAccount($serialized);

        return Deserializer::deserializeArray($response->getResponseBody(), LoyaltyAccountCreateResponse::class);
    }

    /**
     * @param LoyaltyAccountEditRequest $request
     *
     * @return LoyaltyAccountEditResponse|null
     * @throws ReflectionException
     */
    public function editLoyaltyAccount(LoyaltyAccountEditRequest $request): ?LoyaltyAccountEditResponse
    {
        $serialized = Serializer::serializeArray($request);
        $response = $this->client->editLoyaltyAccount($serialized, $request->id);

        return Deserializer::deserializeArray($response->getResponseBody(), LoyaltyAccountEditResponse::class);
    }

    /**
     * @param LoyaltyAccountActivateRequest $request
     *
     * @return LoyaltyAccountActivateResponse|null
     * @throws ReflectionException
     */
    public function activateLoyaltyAccount(LoyaltyAccountActivateRequest $request): ?LoyaltyAccountActivateResponse
    {
        $serialized = Serializer::serializeArray($request);
        $response   = $this->client->activateLoyaltyAccount($serialized['id']);

        return Deserializer::deserializeArray($response->getResponseBody(), LoyaltyAccountActivateResponse::class);
    }


    /**
     * @param SmsVerificationConfirmRequest $request
     *
     * @return LoyaltyAccountActivateResponse|null
     * @throws ReflectionException
     */
    public function sendVerificationCode(SmsVerificationConfirmRequest $request): ?SmsVerificationConfirmResponse
    {
        $serialized = Serializer::serializeArray($request);
        $response   = $this->client->sendVerificationCode($serialized);

        return Deserializer::deserializeArray($response->getResponseBody(), SmsVerificationConfirmResponse::class);
    }

    /**
     * @param int $loyaltyId
     *
     * @return LoyaltyLoyaltyResponse|null
     */
    public function getLoyaltyLoyalty(int $loyaltyId): ?LoyaltyLoyaltyResponse
    {
        $response = $this->client->getLoyaltyLoyalty($loyaltyId);

        return Deserializer::deserializeArray($response->getResponseBody(), LoyaltyLoyaltyResponse::class);
    }

    /**
     * @return LoyaltyLoyaltiesResponse|null
     */
    public function getLoyaltyLoyalties(): ?LoyaltyLoyaltiesResponse
    {
        $response = $this->client->getLoyaltyLoyalties();

        return Deserializer::deserializeArray($response->getResponseBody(), LoyaltyLoyaltiesResponse::class);
    }
}
