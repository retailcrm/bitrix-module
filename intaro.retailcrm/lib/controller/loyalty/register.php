<?php

namespace Intaro\RetailCrm\Controller\Loyalty;

use Bitrix\Main\Engine\ActionFilter\Authentication;
use Bitrix\Main\Engine\Controller;
use Intaro\RetailCrm\Component\ConfigProvider;
use Intaro\RetailCrm\Component\Constants;
use Intaro\RetailCrm\Component\Factory\ClientFactory;
use Intaro\RetailCrm\Model\Api\Request\Loyalty\Account\LoyaltyAccountActivateRequest;
use Intaro\RetailCrm\Model\Api\Request\Loyalty\Account\LoyaltyAccountCreateRequest;
use Intaro\RetailCrm\Model\Api\Request\SmsVerification\SmsVerificationConfirmRequest;
use Intaro\RetailCrm\Model\Api\Response\SmsVerification\SmsVerificationStatusRequest;
use Intaro\RetailCrm\Model\Api\SerializedCreateLoyaltyAccount;
use Intaro\RetailCrm\Model\Api\SmsVerificationConfirm;
use Intaro\RetailCrm\Model\Bitrix\User;

class Register extends Controller
{
    public const MIN_CODE_LENGTH = 3;
    public const MAX_CODE_LENGTH = 11;
    
    /**
     * @param array $loyaltyAccount
     * @return array|string[]
     */
    public function accountCreateAction(array $loyaltyAccount): array
    {
        $phoneNumber = preg_replace('/\s|\+|-|\(|\)/', '', $loyaltyAccount['phone']);
        
        if (!is_numeric($phoneNumber)) {
            return [
                'status'   => 'error',
                'errorMsg' => 'Некорректный номер телефона',
            ];
        }
        
        $user = User::getEntityByPrimary($loyaltyAccount['customerId']);
    
        global $USER_FIELD_MANAGER;
    
        $USER_FIELD_MANAGER->Update('USER', $loyaltyAccount['customerId'], [
            'UF_CARD_NUM_INTARO' => $loyaltyAccount['card'],
        ]);
    
        if (empty($user->getPersonalPhone())) {
            $user->setPersonalPhone($loyaltyAccount['phone']);
            $user->save();
        }
        
        //TODO когда станет известен формат карты ПЛ, то добавить валидацию ввода
        
        /** @var \Intaro\RetailCrm\Component\ApiClient\ClientAdapter $client */
        $client      = ClientFactory::createClientAdapter();
        $credentials = $client->getCredentials();
        
        $createRequest                               = new LoyaltyAccountCreateRequest();
        $createRequest->site                         = $credentials->sitesAvailable[0];
        $createRequest->loyaltyAccount               = new SerializedCreateLoyaltyAccount();
        $createRequest->loyaltyAccount->phoneNumber  = $loyaltyAccount['phone'] ?? '';
        $createRequest->loyaltyAccount->cardNumber   = $loyaltyAccount['card'] ?? '';
        $createRequest->loyaltyAccount->customerId   = $loyaltyAccount['customerId'];
        $createRequest->loyaltyAccount->customFields = $loyaltyAccount['customFields'] ?? [];
        
        $createResponse = $client->createLoyaltyAccount($createRequest);
        //TODO добавить провеку на кастомные поля, когда будет готов метод запроса
        if ($createResponse !== null) {
            
            if ($createResponse->success === false) {
                return [
                    'status'   => 'error',
                    'errorMsg' => $createResponse->errorMsg,
                ];
            }
            
            //если участник ПЛ создан и активирован
            if ($createResponse->loyaltyAccount->active) {
                return ['status' => 'activate'];
            }
            
            $activateRequest            = new LoyaltyAccountActivateRequest();
            $activateRequest->loyaltyId = $createResponse->loyaltyAccount->id;
            $activateResponse           = $client->activateLoyaltyAccount($activateRequest);
            
            if (isset($activateResponse->verification)) {
                return ['status' => 'smsVerification'];
            }
        }
        
        return [
            'status'   => 'error',
            'errorMsg' => 'Ошибка запроса',
        ];
    }
    
    /**
     * @param string $code
     * @return array
     */
    public function sendVerificationCodeAction(string $code): array
    {
        $code       = trim($code);
        $lengthCode = strlen($code);
        
        if (empty($code) && $lengthCode > self::MIN_CODE_LENGTH && $lengthCode < self::MAX_CODE_LENGTH) {
            return [
                'status'   => 'error',
                'errorMsg' => 'Код не введен',
            ];
        }
        
        $smsVerification                     = new SmsVerificationConfirmRequest();
        $smsVerification->verification       = new SmsVerificationConfirm();
        $smsVerification->verification->code = $code;
        
        /** @var \Intaro\RetailCrm\Component\ApiClient\ClientAdapter $client */
        $client             = ClientFactory::createClientAdapter();
        $verificationResult = $client->sendVerificationCode($smsVerification);
        
        if ($verificationResult === null) {
            return [
                'status'   => 'error',
                'errorMsg' => 'ОШибка запроса на подтверждение',
            ];
        }
        
        if ($verificationResult->success === false) {
            $errMsg = $verificationResult->errorMsg ?? '';
            
            return [
                'status'   => 'error',
                'errorMsg' => 'Ошибка. ' . $errMsg,
            ];
        }
        
        if ($verificationResult->success === true
            && isset($verificationResult->verification->verifiedAt)
            && !empty($verificationResult->verification->verifiedAt)) {
            
            return [ 'status'   => 'complete'];
        }
    
        return [
            'status'   => 'error',
            'errorMsg' => 'Ошибка.',
        ];
    }
}
