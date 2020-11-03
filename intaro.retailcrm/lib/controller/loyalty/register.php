<?php

namespace Intaro\RetailCrm\Controller\Loyalty;

use Bitrix\Main\Engine\Controller;
use Bitrix\Main\Request;
use Intaro\RetailCrm\Component\Factory\ClientFactory;
use Intaro\RetailCrm\Model\Api\Request\SmsVerification\SmsVerificationConfirmRequest;
use Intaro\RetailCrm\Model\Api\SmsVerificationConfirm;
use Intaro\RetailCrm\Model\Bitrix\User;
use Intaro\RetailCrm\Service\LpUserAccountService;

class Register extends Controller
{
    public const MIN_CODE_LENGTH = 3;
    public const MAX_CODE_LENGTH = 11;
    
    /**
     * Register constructor.
     * @param \Bitrix\Main\Request|null $request
     */
    public function __construct(Request $request = null)
    {
        IncludeModuleLangFile(__FILE__);
        parent::__construct($request);
    }
    
    /**
     * @param array $loyaltyAccount
     * @return array|string[]
     */
    public function accountCreateAction(array $loyaltyAccount): array
    {
        $phoneNumber = $this->phoneValidate($loyaltyAccount['phone']);
    
        if (!is_numeric($phoneNumber)) {
            return [
                'status'   => 'error',
                'msg'      => GetMessage('PHONE_ERROR'),
                'msgColor' => 'brown',
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
        
        $service = new LpUserAccountService();
        $createResponse = $service->createLoyaltyAccount($loyaltyAccount['phone'], $loyaltyAccount['card'], (string) $loyaltyAccount['customerId'], $loyaltyAccount['customFields']);
        //TODO добавить провеку на кастомные поля, когда будет готов метод запроса
        if ($createResponse !== null) {
            if ($createResponse->success === false) {
                return [
                    'status'   => 'error',
                    'msg' => $createResponse->errorMsg,
                    'msgColor' => 'brown'
                ];
            }
            
            //если участник ПЛ создан и активирован
            if ($createResponse->loyaltyAccount->active) {
                return [
                    'status' => 'activate',
                    'msg' =>  GetMessage('SUCCESS_REGISTER'),
                    'msgColor' => 'green'
                ];
            }
    
           $activateResponse = $service->activateLoyaltyAccount($createResponse->loyaltyAccount->id);
            
            if (isset($activateResponse->verification)) {
                return ['status' => 'smsVerification'];
            }
        }
        
        return [
            'status'   => 'error',
            'msg' => GetMessage('REQUEST_ERROR'),
            'msgColor' => 'brown'
        ];
    }
    
    /**
     * Валидирует телефон
     *
     * @param string $phoneNumber
     * @return string|string[]|null
     */
    private function phoneValidate(string $phoneNumber){
        $phoneNumber = preg_replace('/\s|\+|-|\(|\)/', '', $phoneNumber);
        
        return $phoneNumber;
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
                'msg' => GetMessage('EMPTY_CODE'),
                'msgColor' => 'brown'
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
                'msg' => GetMessage('CONFIRMATION_ERROR'),
                'msgColor' => 'brown'
            ];
        }
        
        if ($verificationResult->success === false) {
            $errMsg = $verificationResult->errorMsg ?? '';
            
            return [
                'status'   => 'error',
                'msg' => GetMessage('ERROR') . $errMsg,
                'msgColor' => 'brown'
            ];
        }
        
        if ($verificationResult->success === true
            && isset($verificationResult->verification->verifiedAt)
            && !empty($verificationResult->verification->verifiedAt)
        ) {
    
            global $USER_FIELD_MANAGER;
            global $USER;
    
           $isUpdate =  $USER_FIELD_MANAGER->Update('USER', $USER->GetID(), [
                'UF_EXT_REG_PL_INTARO' => 'Y',
            ]);
            
           if ($isUpdate) {
               return [
                   'status' => 'activate',
                   'msg' => GetMessage('SUCCESS_REGISTER'),
                   'msgColor' => 'green'
               ];
           }
    
            return [
                'status'   => 'error',
                'msg' => GetMessage('STATUS_ADD_ERROR'),
                'msgColor' => 'brown'
            ];
        }
    
        return [
            'status'   => 'error',
            'msg' => GetMessage('ERROR'),
            'msgColor' => 'brown'
        ];
    }
}
