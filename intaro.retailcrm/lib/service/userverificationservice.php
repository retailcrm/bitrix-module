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
use Intaro\RetailCrm\Model\Api\Request\SmsVerification\SmsVerificationConfirmRequest;
use Intaro\RetailCrm\Model\Api\Request\SmsVerification\SmsVerificationCreateRequest;
use Intaro\RetailCrm\Model\Api\Response\SmsVerification\SmsVerificationStatusRequest;
use Intaro\RetailCrm\Model\Api\SmsVerificationConfirm;
use Intaro\RetailCrm\Model\Api\SmsVerificationCreate;
use Intaro\RetailCrm\Repository\UserRepository;

/**
 * Class UserVerificationService
 */
class UserVerificationService
{
    const NOT_AUTHORIZE       = 'Пользователь не авторизован';
    const DEFAULT_CODE_LENGHT = 4;
    
    /**
     * @var \Intaro\RetailCrm\Component\ApiClient\ClientAdapter
     */
    private $client;
    private $userId;
    
    /**
     * @var \CAllUser|\CUser
     */
    private $user;
    
    public function __construct()
    {
        $this->client = ClientFactory::createClientAdapter();
    }
    
    /**
     * send verification sms
     *
     * @param string   $actionType [verify_customer|verify_privacy]
     * @param int|null $orderId
     * @param int      $verificationLength
     * @return \Intaro\RetailCrm\Model\Api\Response\SmsVerification\SmsVerificationCreateResponse|null
     * @throws \Exception
     */
    public function sendSms(
        string $actionType = 'verify_customer',
        int $orderId = null,
        int $verificationLength = self::DEFAULT_CODE_LENGHT
    ) {
        $this->checkAuth();
        $userId = $this->user->GetID();
        
        /** @var \Intaro\RetailCrm\Model\Bitrix\User $user */
        $user = UserRepository::getFirstByParams(
            [
                ['ID', '=', $userId]
            ],
            ['PERSONAL_PHONE']
        );
        $request               = new SmsVerificationCreateRequest();
        $request->verification = new SmsVerificationCreate();
        
        $request->verification->setLength($verificationLength);
        $request->verification->setPhone($user->getPersonalPhone());
        $request->verification->setPhone($this->userId);
        $request->verification->setActionType($actionType);
        $request->verification->setCustomerId($userId);
        $request->verification->setOrderId($orderId);
        
        return $this->client->sendSmsForLpVerification($request);
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
     * @throws \Exception
     */
    private function checkAuth()
    {
        global $USER;
        $this->user = $USER;
        if (!$this->user->IsAuthorized()) {
            throw new Exception(self::NOT_AUTHORIZE);
        }
    }
}
