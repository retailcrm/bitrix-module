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

use Exception;
use Intaro\RetailCrm\Component\Factory\ClientFactory;
use Intaro\RetailCrm\Model\Api\Request\Loyalty\Account\LoyaltyAccountActivateRequest;
use Intaro\RetailCrm\Model\Api\Request\Loyalty\Account\LoyaltyAccountCreateRequest;
use Intaro\RetailCrm\Model\Api\Request\SmsVerification\SmsVerificationConfirmRequest;
use Intaro\RetailCrm\Model\Api\Response\Loyalty\Account\LoyaltyAccountActivateResponse;
use Intaro\RetailCrm\Model\Api\Response\Loyalty\Account\LoyaltyAccountCreateResponse;
use Intaro\RetailCrm\Model\Api\Response\SmsVerification\SmsVerificationStatusRequest;
use Intaro\RetailCrm\Model\Api\SerializedCreateLoyaltyAccount;
use Intaro\RetailCrm\Model\Api\SmsVerificationConfirm;
use RuntimeException;

/**
 * Class UserVerificationService
 */
class UserAccountService
{
    public const NOT_AUTHORIZE = 'Пользователь на авторизован';
    /**
     * @var \Intaro\RetailCrm\Component\ApiClient\ClientAdapter
     */
    private $client;
    
    /**
     * UserAccountService constructor.
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
     * Подтверждает верификацию
     *
     * @param string $code    Проверочный код
     * @param string $checkId Идентификатор проверки кода
     * @return \Intaro\RetailCrm\Model\Api\Response\SmsVerification\SmsVerificationConfirmResponse|null
     */
    public function confirmVerification(string $code, string $checkId)
    {
        $request               = new SmsVerificationConfirmRequest();
        $request->verification = new SmsVerificationConfirm();
        $request->verification->setCode($code);
        $request->verification->setCheckId($checkId);
        
        return $this->client->confirmLpVerificationBySMS($request);
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
        return $this->client->activateLoyaltyAccount($activateRequest);
    }
    
    /**
     * @param string $phone
     * @param string $card
     * @param string $externalId
     * @param array  $customFields
     * @return \Intaro\RetailCrm\Model\Api\Response\Loyalty\Account\LoyaltyAccountCreateResponse|null
     */
    public function createLoyaltyAccount(string $phone, string $card, string $externalId, array $customFields=[]): ?LoyaltyAccountCreateResponse
    {
        $credentials = $this->client->getCredentials();
    
        $createRequest                                       = new LoyaltyAccountCreateRequest();
        $createRequest->site                                 = $credentials->sitesAvailable[0];
        $createRequest->loyaltyAccount                       = new SerializedCreateLoyaltyAccount();
        $createRequest->loyaltyAccount->phoneNumber          = $phone ?? '';
        $createRequest->loyaltyAccount->cardNumber           = $card ?? '';
        $createRequest->loyaltyAccount->customer->externalId = $externalId;
        $createRequest->loyaltyAccount->customFields         = $customFields ?? [];

        return $this->client->createLoyaltyAccount($createRequest);
    }
    
    /**
     * @param $ufExtRegPlIntaro
     * @return array
     */
    public function getExtFields($ufExtRegPlIntaro): array
    {
        //узнаем, какие поля не заполненые у этого пользователя
        //если все заполнено, то пытаемся активировать
        //если в ответ получаем номер кода подтверждения, то отдаем два поля: скрытое с номером отправки и инпут для ввода кода проверки
    }
    
    /**
     * @param \Intaro\RetailCrm\Model\Api\Response\Loyalty\Account\LoyaltyAccountCreateResponse|null $createResponse
     * @param int                                                                                    $userId
     */
    public function activateLpUserInBitrix(?LoyaltyAccountCreateResponse $createResponse, int $userId): void
    {
        //если участник ПЛ создан и активирован
        if (($createResponse !== null)
            && $createResponse->success === true
            && $createResponse->loyaltyAccount->active
        ) {
            global $USER_FIELD_MANAGER;
        
            $USER_FIELD_MANAGER->Update('USER', $userId, [
                'UF_EXT_REG_PL_INTARO' => 'Y',
                'UF_LP_ID_INTARO' => $createResponse->loyaltyAccount->id,
            ]);
        }
    
        if (isset($createResponse->errorMsg)) {
            AddMessage2Log($createResponse->errorMsg);
        }
    }
    
    /**
     * @throws \Exception
     */
    private function checkAuth()
    {
        global $USER;
        $user = $USER;
        
        if (!$user->IsAuthorized()) {
            throw new RuntimeException(self::NOT_AUTHORIZE);
        }
    }
}
