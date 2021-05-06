<?php

/**
 * PHP version 7.1
 *
 * @category Integration
 * @package  Intaro\RetailCrm\Service
 * @author   RetailCRM <integration@retailcrm.ru>
 * @license  MIT
 * @link     http://retailcrm.ru
 * @see      http://retailcrm.ru/docs
 */

namespace Intaro\RetailCrm\Service;

use CUser;
use DateTime;
use Intaro\RetailCrm\Component\Factory\ClientFactory;
use Intaro\RetailCrm\Component\Json\Serializer;
use Intaro\RetailCrm\Component\ServiceLocator;
use Intaro\RetailCrm\Model\Api\Request\Loyalty\Account\LoyaltyAccountActivateRequest;
use Intaro\RetailCrm\Model\Api\Request\Loyalty\Account\LoyaltyAccountCreateRequest;
use Intaro\RetailCrm\Model\Api\Request\SmsVerification\SmsVerificationConfirmRequest;
use Intaro\RetailCrm\Model\Api\Response\Loyalty\Account\LoyaltyAccountActivateResponse;
use Intaro\RetailCrm\Model\Api\Response\Loyalty\Account\LoyaltyAccountCreateResponse;
use Intaro\RetailCrm\Model\Api\Response\SmsVerification\SmsVerificationConfirmResponse;
use Intaro\RetailCrm\Model\Api\Response\SmsVerification\SmsVerificationStatusRequest;
use Intaro\RetailCrm\Model\Api\Response\SmsVerification\SmsVerificationStatusResponse;
use Intaro\RetailCrm\Model\Api\SerializedCreateLoyaltyAccount;
use Intaro\RetailCrm\Model\Api\SmsVerificationConfirm;
use Intaro\RetailCrm\Model\Bitrix\User;
use Intaro\RetailCrm\Model\Bitrix\UserLoyaltyData;
use Intaro\RetailCrm\Repository\UserRepository;

/**
 * Class LoyaltyAccountService
 */
class LoyaltyAccountService
{
    public const STANDARD_FIELDS = [
        'UF_AGREE_PL_INTARO'   => 'checkbox',
        'UF_PD_PROC_PL_INTARO' => 'checkbox',
        'PERSONAL_PHONE'       => 'text',
    ];
    
    /**
     * @var \Intaro\RetailCrm\Model\Bitrix\User|null
     */
    private $user;

    /**
     * Получает статус текущего состояния верификации
     *
     * @param string $checkId Идентификатор проверки кода
     * @return \Intaro\RetailCrm\Model\Api\Response\SmsVerification\SmsVerificationStatusResponse|null
     */
    public function getSmsStatus(string $checkId): ?SmsVerificationStatusResponse
    {
        /** @var \Intaro\RetailCrm\Component\ApiClient\ClientAdapter $client */
        $client           = ClientFactory::createClientAdapter();
        $request          = new SmsVerificationStatusRequest();
        $request->checkId = $checkId;

        return $client->checkStatusPlVerification($request);
    }

    /**
     * @param int $loyaltyId
     * @return \Intaro\RetailCrm\Model\Api\Response\Loyalty\Account\LoyaltyAccountActivateResponse|null
     */
    public function activateLoyaltyAccount(int $loyaltyId): ?LoyaltyAccountActivateResponse
    {
        /** @var \Intaro\RetailCrm\Component\ApiClient\ClientAdapter $client */
        $client = ClientFactory::createClientAdapter();

        $activateRequest            = new LoyaltyAccountActivateRequest();
        $activateRequest->loyaltyId = $loyaltyId;

        $response = $client->activateLoyaltyAccount($activateRequest);

        if ($response === null) {
            return null;
        }

        if ($response->success && $response->loyaltyAccount->activatedAt instanceof DateTime) {
            return $response;
        }

        Utils::handleApiErrors($response);

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
        /** @var \Intaro\RetailCrm\Component\ApiClient\ClientAdapter $client */
        $client = ClientFactory::createClientAdapter();

        $credentials = $client->getCredentials();

        $createRequest                                       = new LoyaltyAccountCreateRequest();
        $createRequest->site                                 = $credentials->sitesAvailable[0];
        $createRequest->loyaltyAccount                       = new SerializedCreateLoyaltyAccount();
        $createRequest->loyaltyAccount->phoneNumber          = $phone ?? '';
        $createRequest->loyaltyAccount->cardNumber           = $card ?? '';
        $createRequest->loyaltyAccount->customer->externalId = $externalId;
        $createRequest->loyaltyAccount->customFields         = $customFields ?? [];

        $createResponse = $client->createLoyaltyAccount($createRequest);

        if ($createResponse instanceof LoyaltyAccountCreateResponse) {
            Utils::handleApiErrors($createResponse, GetMessage('REGISTER_ERROR'));
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

        Utils::handleApiErrors($createResponse, GetMessage('REGISTER_ERROR'));
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
        /** @var \Intaro\RetailCrm\Component\ApiClient\ClientAdapter $client */
        $client = ClientFactory::createClientAdapter();

        $request                        = new SmsVerificationConfirmRequest();
        $request->verification          = new SmsVerificationConfirm();
        $request->verification->code    = $code;
        $request->verification->checkId = $checkId;

        $response = $client->sendVerificationCode($request);

        if ($response !== null) {
            Utils::handleApiErrors($response, GetMessage('DEBITING_BONUSES_ERROR'));
        }

        return $response;
    }
    
    /**
     * Возвращает статус пользователя в системе лояльности
     *
     * @return bool
     */
    public static function getLoyaltyPersonalStatus(): bool
    {
        global $USER;
        $userFields = CUser::GetByID($USER->GetID())->Fetch();
        
        return isset($userFields['UF_EXT_REG_PL_INTARO']) && $userFields['UF_EXT_REG_PL_INTARO'] === '1';
    }
    
    /**
     * @return array|null
     */
    public function checkRegInLp(): ?array
    {
        global $USER;
        
        if ($USER->IsAuthorized()) {
            $this->user = UserRepository::getById($USER->GetID());
        }
        
        if (!$this->user) {
            return [];
        }
        
        $loyalty = $this->user->getLoyalty();
        
        //Изъявлял ли ранее пользователь желание участвовать в ПЛ?
        if ($loyalty->getIsAgreeRegisterInLoyaltyProgram() === 1) {
            //ДА. Существует ли у него аккаунт?
            if (!empty($loyalty->getIdInLoyalty())) {
                //ДА. Активен ли его аккаунт?
                if ($loyalty->getIsUserLoyaltyAccountActive() === 1) {
                    //ДА. Отображаем сообщение "Вы зарегистрированы в Программе лояльности"
                    return ['msg' => GetMessage('REG_COMPLETE')];
                }
                
                //НЕТ. Аккаунт не активен
                $extFields   = $this->getExtFields($loyalty->getIdInLoyalty());
                
                //Есть ли обязательные поля, которые нужно заполнить для завершения активации?
                if (!empty($extFields)) {
                    //Да, есть незаполненные обязательные поля
                    return [
                        'msg'  => GetMessage('ACTIVATE_YOUR_ACCOUNT'),
                        'form' => [
                            'button' => [
                                'name'   => GetMessage('ACTIVATE'),
                                'action' => 'activateAccount',
                            ],
                            'fields' => $extFields,
                        ],
                    ];
                }
                
                return $this->tryActivate($loyalty->getIdInLoyalty());
            }
            
            //Аккаунт не существует. Выясняем, каких полей не хватает для СОЗДАНИЯ аккаунта, выводим форму
            $fields = $this->getFields($this->user);
            
            //Если все необходимые поля заполнены, то пытаемся его еще раз зарегистрировать
            if (count($fields) === 0) {
                $customFields   = $this->getExternalFields();
                $createResponse = $this->registerAndActivateUser($this->user->getId(), $this->user->getPersonalPhone(), $customFields, $loyalty);
                
                if ($createResponse === false) {
                    header('Refresh 0');
                }
                
                return $createResponse;
            }
            
            return [
                'msg'  => GetMessage('COMPLETE_YOUR_REGISTRATION'),
                'form' => [
                    'button' => [
                        'name'   => GetMessage('CREATE'),
                        'action' => 'createAccount',
                    ],
                    'fields' => $this->getFields($this->user),
                ],
            ];
        }
        
        //НЕТ. Отображаем форму на создание новой регистрации в ПЛ
        return [
            'msg'  => GetMessage('INVITATION_TO_REGISTER'),
            'form' => [
                'button' => [
                    'name'   => GetMessage('CREATE'),
                    'action' => 'createAccount',
                ],
                'fields' => $this->getFields($this->user),
            ],
        ];
    }
    
    /**
     * @param int $idInLoyalty
     *
     * @return array
     */
    public function tryActivate(int $idInLoyalty): array
    {
        /** @var \Intaro\RetailCrm\Service\CookieService $service */
        $service = ServiceLocator::get(CookieService::class);
        
        $smsCookie   = $service->getSmsCookie('lpRegister');
        $nowTime     = new DateTime();
        
        if ($smsCookie !== null
            && isset($smsCookie->resendAvailable)
            && $smsCookie->resendAvailable > $nowTime
        ) {
            return [
                'msg'             => GetMessage('SMS_VERIFICATION'),
                'form'            => [
                    'button' => [
                        'name'   => GetMessage('SEND'),
                        'action' => 'sendVerificationCode',
                    ],
                    'fields' => [
                        'smsVerificationCode' => [
                            'type' => 'text',
                        ],
                        'checkId'             => [
                            'type'  => 'hidden',
                            'value' => $smsCookie->checkId,
                        ],
                    ],
                ],
                'resendAvailable' => $smsCookie->resendAvailable->format('Y-m-d H:i:s'),
                'idInLoyalty'     => $idInLoyalty,
            ];
        }
        
        //Пробуем активировать аккаунт
        $activateResponse = $this->activateLoyaltyAccount($idInLoyalty);
        
        if ($activateResponse !== null
            && isset($activateResponse->loyaltyAccount->active)
            && $activateResponse->loyaltyAccount->active === true
        ) {
            return ['msg' => GetMessage('REG_COMPLETE')];
        }
        
        //нужна смс верификация
        if (isset($activateResponse->verification, $activateResponse->verification->checkId)
            && $activateResponse !== null
            && !isset($activateResponse->verification->verifiedAt)
        ) {
            $smsCookie = $service->setSmsCookie('lpRegister', $activateResponse->verification);
            
            return [
                'msg'             => GetMessage('SMS_VERIFICATION'),
                'form'            => [
                    'button' => [
                        'name'   => GetMessage('SEND'),
                        'action' => 'sendVerificationCode',
                    ],
                    'fields' => [
                        'smsVerificationCode' => [
                            'type' => 'text',
                        ],
                        'checkId'             => [
                            'type'  => 'hidden',
                            'value' => $smsCookie->checkId,
                        ],
                    ],
                ],
                'resendAvailable' => $smsCookie->resendAvailable->format('Y-m-d H:i:s'),
                'idInLoyalty'     => $idInLoyalty,
            ];
        }
        
        return ['msg' => GetMessage('ACTIVATE_ERROR') . ' ' . $activateResponse->errorMsg ?? ''];
    }
    
    /**
     * @param \Intaro\RetailCrm\Model\Bitrix\User $user
     * @return array
     */
    private function getFields(User $user): array
    {
        $standardFields = $this->getStandardFields($user);
        $externalFields = $this->getExternalFields();
        
        return array_merge($standardFields, $externalFields);
    }
    
    /**
     * TODO реализовать получение кастомных полей из CRM (когда это появится в API)
     * @return array
     */
    private function getExternalFields(): array
    {
        return [];
    }
    
    /**
     * @param \Intaro\RetailCrm\Model\Bitrix\User $user
     * @return array
     */
    private function getStandardFields(User $user): array
    {
        
        $resultFields                 = [];
        $userFields                   = Serializer::serializeArray($user->getLoyalty());
        $userFields['PERSONAL_PHONE'] = $user->getPersonalPhone();
        
        foreach (self::STANDARD_FIELDS as $key => $value) {
            if ($value === 'text' && empty($userFields[$key])) {
                $resultFields[$key] = [
                    'type' => $value,
                ];
            }
            
            if ($value === 'checkbox' && $userFields[$key] !== 1) {
                $resultFields[$key] = [
                    'type' => $value,
                ];
            }
        }
        
        return $resultFields;
    }

    /**
     * @param int                                            $userId
     * @param string                                         $userPhone
     * @param array                                          $customFields
     * @param \Intaro\RetailCrm\Model\Bitrix\UserLoyaltyData $loyalty
     * @return array|string[]|null
     */
    private function registerAndActivateUser(
        int $userId,
        string $userPhone,
        array $customFields,
        UserLoyaltyData $loyalty
    ): ?array {
        $phone      = $userPhone ?? '';
        $card       = $loyalty->getBonusCardNumber() ?? '';
        $customerId = (string)$userId;
        
        $createResponse = $this->createLoyaltyAccount($phone, $card, $customerId, $customFields);
    
        $this->activateLpUserInBitrix($createResponse, $userId);
        
        $errorMsg = Utils::getErrorMsg($createResponse);
        
        if ($errorMsg !== null) {
            return ['msg' => $errorMsg];
        }
        
        /**
         * создать получилось, но аккаунт не активен
         */
        if ($createResponse !== null
            && $createResponse->success === true
            && $createResponse->loyaltyAccount->active === false
            && $createResponse->loyaltyAccount->activatedAt === null
            && isset($createResponse->loyaltyAccount->id)
        ) {
            return $this->tryActivate($createResponse->loyaltyAccount->id);
        }
        
        if ($createResponse !== null && $createResponse->success === true) {
            //Повторная регистрация оказалась удачной
            return ['msg' => GetMessage('REG_COMPLETE')];
        }
    }
}
