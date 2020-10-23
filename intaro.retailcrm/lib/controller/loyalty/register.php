<?php

namespace Intaro\RetailCrm\Controller\Loyalty;

use Bitrix\Main\Engine\Controller;
use Intaro\RetailCrm\Component\Factory\ClientFactory;
use Intaro\RetailCrm\Model\Api\Request\SmsVerification\SmsVerificationConfirmRequest;
use Intaro\RetailCrm\Model\Api\SmsVerificationConfirm;
use Intaro\RetailCrm\Model\Bitrix\User;
use Intaro\RetailCrm\Service\UserAccountService;
use RetailCrm\ApiClient;

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
                'msg' => 'Некорректный номер телефона',
                'msgColor' => 'brown'
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
        
        $service = new UserAccountService();
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
                    'msg' => 'Регистрация в программе лояльности успешно завершена',
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
            'msg' => 'Ошибка запроса',
            'msgColor' => 'brown'
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
                'msg' => 'Код не введен',
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
                'msg' => 'ОШибка запроса на подтверждение',
                'msgColor' => 'brown'
            ];
        }
        
        if ($verificationResult->success === false) {
            $errMsg = $verificationResult->errorMsg ?? '';
            
            return [
                'status'   => 'error',
                'msg' => 'Ошибка. ' . $errMsg,
                'msgColor' => 'brown'
            ];
        }
        
        if ($verificationResult->success === true
            && isset($verificationResult->verification->verifiedAt)
            && !empty($verificationResult->verification->verifiedAt)) {
    
            global $USER_FIELD_MANAGER;
            global $USER;
    
           $isUpdate =  $USER_FIELD_MANAGER->Update('USER', $USER->GetID(), [
                'UF_EXT_REG_PL_INTARO' => 'Y',
            ]);
            
           if ($isUpdate) {
               return [
                   'status' => 'activate',
                   'msg' => 'Регистрация в программе лояльности успешно завершена',
                   'msgColor' => 'green'
               ];
           }
    
            return [
                'status'   => 'error',
                'msg' => 'Регистрация прошла успешно, но статус не был сохранен в БД сайта',
                'msgColor' => 'brown'
            ];
        }
    
        return [
            'status'   => 'error',
            'msg' => 'Ошибка.',
            'msgColor' => 'brown'
        ];
    }
}
