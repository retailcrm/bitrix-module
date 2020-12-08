<?php
/**
 * PHP version 7.1
 *
 * @category Integration
 * @package  Intaro\RetailCrm\Service
 * @author   retailCRM <integration@retailcrm.ru>
 * @license  MIT
 * @link     http://retailcrm.ru
 * @see      http://retailcrm.ru/docs
 */

namespace Intaro\RetailCrm\Service;

use DateTime;
use Intaro\RetailCrm\Component\Factory\ClientFactory;
use Intaro\RetailCrm\Model\Api\Request\Loyalty\Account\LoyaltyAccountActivateRequest;
use Intaro\RetailCrm\Model\Api\Request\Loyalty\Account\LoyaltyAccountCreateRequest;
use Intaro\RetailCrm\Model\Api\Request\SmsVerification\SmsVerificationConfirmRequest;
use Intaro\RetailCrm\Model\Api\Response\Loyalty\Account\LoyaltyAccountActivateResponse;
use Intaro\RetailCrm\Model\Api\Response\Loyalty\Account\LoyaltyAccountCreateResponse;
use Intaro\RetailCrm\Model\Api\Response\SmsVerification\SmsVerificationConfirmResponse;
use Intaro\RetailCrm\Model\Api\Response\SmsVerification\SmsVerificationStatusRequest;
use Intaro\RetailCrm\Model\Api\SerializedCreateLoyaltyAccount;
use Intaro\RetailCrm\Model\Api\SmsVerificationConfirm;

/**
 * Class UserAccountService
 */
class LpUserAccountService
{
    public const NOT_AUTHORIZE = 'Пользователь на авторизован';
    
    /**
     * @var \Intaro\RetailCrm\Component\ApiClient\ClientAdapter
     */
    private $client;
    
    /**
     * LpUserAccountService constructor.
     */
    public function __construct()
    {
        $this->client = ClientFactory::createClientAdapter();
    }
    
    /**
     * Получает статус текущего состояния верификации
     *
     * @param string $checkId Идентификатор проверки кода
     * @return \Intaro\RetailCrm\Model\Api\Response\SmsVerification\SmsVerificationStatusResponse|null
     */
    public function getSmsStatus(string $checkId)
    {
        $request          = new SmsVerificationStatusRequest();
        $request->checkId = $checkId;
        
        return $this->client->checkStatusPlVerification($request);
    }
    
    /**
     * Проверяем статус регистрации пользователя в ПЛ
     *
     * @param int $userId
     * @return bool
     */
    public function checkPlRegistrationStatus(int $userId)
    {
        //TODO когда метод будет реализован в АПИ, нужно будет написать реализацию
        return true;
    }
    
    /**
     * @param int $loyaltyId
     * @return \Intaro\RetailCrm\Model\Api\Response\Loyalty\Account\LoyaltyAccountActivateResponse|null
     */
    public function activateLoyaltyAccount(int $loyaltyId): ?LoyaltyAccountActivateResponse
    {
        $activateRequest            = new LoyaltyAccountActivateRequest();
        $activateRequest->loyaltyId = $loyaltyId;
    
        $response = $this->client->activateLoyaltyAccount($activateRequest);
        
        if ($response === null) {
            return null;
        }
    
        if ($response->success && $response->loyaltyAccount->activatedAt instanceof DateTime) {
            return $response;
        }
        
        Utils::handleErrors($response);
        
        return $response;
    }
    
    /**
     * @param string $phone
     * @param string $card
     * @param string $externalId
     * @param array  $customFields
     * @return \Intaro\RetailCrm\Model\Api\Response\Loyalty\Account\LoyaltyAccountCreateResponse|null
     */
    public function createLoyaltyAccount(string $phone, string $card, string $externalId, array $customFields = []): ?LoyaltyAccountCreateResponse
    {
        $credentials = $this->client->getCredentials();
        
        $createRequest                                       = new LoyaltyAccountCreateRequest();
        $createRequest->site                                 = $credentials->sitesAvailable[0];
        $createRequest->loyaltyAccount                       = new SerializedCreateLoyaltyAccount();
        $createRequest->loyaltyAccount->phoneNumber          = $phone ?? '';
        $createRequest->loyaltyAccount->cardNumber           = $card ?? '';
        $createRequest->loyaltyAccount->customer->externalId = $externalId;
        $createRequest->loyaltyAccount->customFields         = $customFields ?? [];
    
        $createResponse = $this->client->createLoyaltyAccount($createRequest);
        
        if ($createResponse instanceof LoyaltyAccountCreateResponse) {
            Utils::handleErrors($createResponse, GetMessage('REGISTER_ERROR'));
        }
        
        return $createResponse;
    }
    
    /**
     * @param $isExternalRegister
     * @return array
     */
    public function getExtFields($isExternalRegister): array
    {
        //TODO Реализовать метод, когда появится возможность получить обязательные поля
        return [];
    }
    
    /**
     * @param \Intaro\RetailCrm\Model\Api\Response\Loyalty\Account\LoyaltyAccountCreateResponse|null $createResponse
     * @param int                                                                                    $userId
     */
    public function activateLpUserInBitrix(?LoyaltyAccountCreateResponse $createResponse, int $userId): void
    {
        //если участник ПЛ создан
        if (($createResponse !== null)
            && $createResponse->success === true
        ) {
            global $USER_FIELD_MANAGER;

            $USER_FIELD_MANAGER->Update('USER', $userId, [
                'UF_EXT_REG_PL_INTARO' => $createResponse->loyaltyAccount->active === true ? 'Y' : '',
                'UF_LP_ID_INTARO'      => $createResponse->loyaltyAccount->id,
            ]);
        }
    
        Utils::handleErrors($createResponse, GetMessage('REGISTER_ERROR'));
    }
    
    /**
     * Подтверждает верификацию
     *
     * @param string $code    Проверочный код
     * @param string $checkId Идентификатор проверки кода
     * @return \Intaro\RetailCrm\Model\Api\Response\SmsVerification\SmsVerificationConfirmResponse|null
     */
    public function confirmVerification(string $code, string $checkId): ?SmsVerificationConfirmResponse
    {
        $request                        = new SmsVerificationConfirmRequest();
        $request->verification          = new SmsVerificationConfirm();
        $request->verification->code    = $code;
        $request->verification->checkId = $checkId;
        
        return $this->client->confirmLpVerificationBySMS($request);
    }
}

