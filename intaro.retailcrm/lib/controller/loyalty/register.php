<?php

namespace Intaro\RetailCrm\Controller\Loyalty;

use Bitrix\Main\Engine\ActionFilter\Authentication;
use Bitrix\Main\Engine\Controller;
use Bitrix\Main\Request;
use Intaro\RetailCrm\Component\Factory\ClientFactory;
use Intaro\RetailCrm\Component\ServiceLocator;
use Intaro\RetailCrm\DataProvider\CurrentUserProvider;
use Intaro\RetailCrm\Model\Api\Request\SmsVerification\SmsVerificationConfirmRequest;
use Intaro\RetailCrm\Model\Api\SmsVerificationConfirm;
use Intaro\RetailCrm\Model\Bitrix\User;
use Intaro\RetailCrm\Service\LoyaltyService;
use Intaro\RetailCrm\Service\LoyaltyAccountService;
use Intaro\RetailCrm\Service\Utils;

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
     * @return \array[][]
     */
    public function configureActions(): array
    {
        return [
            'saveUserLpFields' => [
                '-prefilters' => [
                    new Authentication,
                ],
            ],
        ];
    }
    
    /**
     * Сохраняет информацию о регистрации пользователя в ПЛ в профиле пользователя
     *
     * @param array $request
     *
     * @return array
     * @throws \ReflectionException
     */
    public function saveUserLpFieldsAction(array $request): array
    {
        global $USER_FIELD_MANAGER;
    
        $msg          = '';
        $cardNumber   = htmlspecialchars(trim($request['UF_CARD_NUM_INTARO'] ?? ''));
        $userProvider = new CurrentUserProvider();
        $customer     = $userProvider->get();
        $phoneNumber  = Utils::filterPhone($request['PERSONAL_PHONE'] ?? '');
        $updateFields = [
            'UF_CARD_NUM_INTARO'  => $cardNumber ?? '',
            'UF_REG_IN_PL_INTARO' => true,
            'UF_AGREE_PL_INTARO' => true,
            'UF_PD_PROC_PL_INTARO' => true,
        ];
        
        if ($customer === null) {
           return [
                'result' => false,
                'msg'    => GetMessage('NOT_REGISTER'),
            ];
        }

        if ((!isset($request['UF_AGREE_PL_INTARO'])
                || $request['UF_AGREE_PL_INTARO'] !== "on")
            && $customer->getLoyalty()->getIsAgreeLoyaltyProgramRules() !== 1
        ) {
            return [
                'result' => false,
                'msg'    => GetMessage('NOT_AGREE_LP_RULES'),
            ];
        }
        
        if ((!isset($request['UF_PD_PROC_PL_INTARO'])
            || $request['UF_PD_PROC_PL_INTARO'] !== "on")
        && $customer->getLoyalty()->getIsAgreePersonalDataRules() !== 1
        ) {
            return [
                'result' => false,
                'msg'    => GetMessage('NOT_AGREE_PERSONAL_DATA_RULES'),
            ];
        }
    
        if (!isset($request['PERSONAL_PHONE'])
            && empty($customer->getPersonalPhone())
        ) {
            return [
                'result' => false,
                'msg'    => GetMessage('PHONE_EMPTY'),
            ];
        }
    
        if (!empty($phoneNumber)) {
            $customer->setPersonalPhone($phoneNumber);
            $customer->save();
        }

        $result = $USER_FIELD_MANAGER->Update('USER', $customer->getId(), $updateFields);
    
        return [
            'result' => $result,
            'msg'    => $msg,
        ];
    }
    
    /**
     * Создает в CRM участие в ПЛ на основе регистрационных данных
     *
     * @param array $request
     *
     * @return array|string[]
     * @throws \ReflectionException
     */
    public function accountCreateAction(array $request): array
    {
        $phoneNumber = Utils::filterPhone($request['phone']);
        
        if (!is_numeric($phoneNumber)) {
            return [
                'status'   => 'error',
                'msg'      => GetMessage('PHONE_ERROR'),
                'msgColor' => 'brown',
            ];
        }
        
        $user = User::getEntityByPrimary($request['customerId']);
        
        global $USER_FIELD_MANAGER;
        
        $USER_FIELD_MANAGER->Update('USER', $request['customerId'], [
            'UF_CARD_NUM_INTARO' => $request['card'],
        ]);
        
        if (empty($user->getPersonalPhone())) {
            $user->setPersonalPhone($request['phone']);
            $user->save();
        }
        
        //TODO когда станет известен формат карты ПЛ, то добавить валидацию ввода
        $service        = new LoyaltyAccountService();
        $createResponse = $service->createLoyaltyAccount(
            $request['phone'],
            $request['card'],
            (string) $request['customerId'],
            $request['customFields']
        );
        
        //TODO добавить провеку на кастомные поля, когда будет готов метод запроса
        if ($createResponse !== null) {
            if ($createResponse->success === false) {
                return [
                    'status'   => 'error',
                    'msg'      => $createResponse->errorMsg,
                    'msgColor' => 'brown',
                ];
            }
            
            //если участник ПЛ создан и активирован
            if ($createResponse->loyaltyAccount->active) {
                return [
                    'status'   => 'activate',
                    'msg'      => GetMessage('SUCCESS_REGISTER'),
                    'msgColor' => 'green',
                ];
            }
            
            $activateResponse = $service->activateLoyaltyAccount($createResponse->loyaltyAccount->id);
            
            if (isset($activateResponse->verification)) {
                return ['status' => 'smsVerification'];
            }
        }
        
        return [
            'status'   => 'error',
            'msg'      => GetMessage('REQUEST_ERROR'),
            'msgColor' => 'brown',
        ];
    }
    
    /**
     * Повторно отправляет смс для активации участия в программе лояльности
     *
     * @param string $idInLoyalty id участия в ПЛ
     * @return string[]|null
     */
    public function resendRegisterSmsAction(string $idInLoyalty): ?array
    {
        if (!is_numeric($idInLoyalty)) {
            return ['msg' => GetMessage('ARGUMENT_ERROR')];
        }
        
        /** @var LoyaltyAccountService $service */
        $service = ServiceLocator::get(LoyaltyAccountService::class);
        
        return $service->tryActivate((int) $idInLoyalty);
    }
    
    /**
     * Активирует участие в ПЛ по коду из СМС
     *
     * @param string $verificationCode Проверочный код из СМС
     * @param string $checkId Идентификатор проверки кода
     *
     * @return array|string[]
     */
    public function activateLpBySmsAction(string $verificationCode, string $checkId): array
    {
        $verificationCode = trim($verificationCode);
        $lengthCode = strlen($verificationCode);
        
        if (empty($verificationCode) && $lengthCode > self::MIN_CODE_LENGTH && $lengthCode < self::MAX_CODE_LENGTH) {
            return [
                'status'   => 'error',
                'msg'      => GetMessage('EMPTY_CODE'),
                'msgColor' => 'brown',
            ];
        }
    
        $smsVerification                        = new SmsVerificationConfirmRequest();
        $smsVerification->verification          = new SmsVerificationConfirm();
        $smsVerification->verification->code    = $verificationCode;
        $smsVerification->verification->checkId = $checkId;
        
        /** @var \Intaro\RetailCrm\Component\ApiClient\ClientAdapter $client */
        $client             = ClientFactory::createClientAdapter();
        $verificationResult = $client->sendVerificationCode($smsVerification);
        
        if ($verificationResult === null) {
            return [
                'status'   => 'error',
                'msg'      => GetMessage('CONFIRMATION_ERROR'),
                'msgColor' => 'brown',
            ];
        }
        
        if ($verificationResult->success === false) {
            $errMsg = $verificationResult->errorMsg ?? '';
            
            return [
                'status'   => 'error',
                'msg'      => GetMessage('ERROR') . $errMsg,
                'msgColor' => 'brown',
            ];
        }
        
        if (
            $verificationResult->success === true
            && isset($verificationResult->verification->verifiedAt)
            && !empty($verificationResult->verification->verifiedAt)
        ) {
            global $USER_FIELD_MANAGER;
            global $USER;
            
            $isUpdate = $USER_FIELD_MANAGER->Update('USER', $USER->GetID(), [
                'UF_EXT_REG_PL_INTARO' => 'Y',
            ]);
            
            if ($isUpdate) {
                return [
                    'status'   => 'activate',
                    'msg'      => GetMessage('SUCCESS_REGISTER'),
                    'msgColor' => 'green',
                ];
            }
            
            return [
                'status'   => 'error',
                'msg'      => GetMessage('STATUS_ADD_ERROR'),
                'msgColor' => 'brown',
            ];
        }
        
        return [
            'status'   => 'error',
            'msg'      => GetMessage('ERROR'),
            'msgColor' => 'brown',
        ];
    }
}
