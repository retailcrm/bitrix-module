<?php

namespace Intaro\RetailCrm\Controller\Loyalty;

use Bitrix\Main\Engine\ActionFilter\Authentication;
use Bitrix\Main\Engine\Controller;
use Bitrix\Main\Request;
use Intaro\RetailCrm\Component\Builder\Api\CustomerBuilder;
use Intaro\RetailCrm\Component\ConfigProvider;
use Intaro\RetailCrm\Component\Factory\ClientFactory;
use Intaro\RetailCrm\Component\ServiceLocator;
use Intaro\RetailCrm\DataProvider\CurrentUserProvider;
use Intaro\RetailCrm\Model\Api\LoyaltyAccount;
use Intaro\RetailCrm\Model\Api\Request\Loyalty\Account\LoyaltyAccountActivateRequest;
use Intaro\RetailCrm\Model\Api\Request\Loyalty\Account\LoyaltyAccountEditRequest;
use Intaro\RetailCrm\Model\Api\Request\SmsVerification\SmsVerificationConfirmRequest;
use Intaro\RetailCrm\Model\Api\Response\Loyalty\Account\LoyaltyAccountEditResponse;
use Intaro\RetailCrm\Model\Api\SmsVerificationConfirm;
use Intaro\RetailCrm\Model\Bitrix\User;
use Intaro\RetailCrm\Repository\UserRepository;
use Intaro\RetailCrm\Service\CustomerService;
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
            'resetUserLpFields' => [
                '-prefilters' => [
                    new Authentication,
                ],
            ],
        ];
    }

    /**
     * Сбрасывает информацию о регистрации пользователя в ПЛ. Позволяет пройти регистрацию заново.
     *
     * @return array
     */
    public function resetUserLpFieldsAction(): array
    {
        global $USER_FIELD_MANAGER;

        $customer = (new CurrentUserProvider())->get();

        if ($customer === null) {
            return [
                'result' => false,
                'msg'    => GetMessage('NOT_REGISTER'),
            ];
        }

        $result = $USER_FIELD_MANAGER->Update('USER', $customer->getId(), [
            'UF_CARD_NUM_INTARO'  => '',
            'UF_REG_IN_PL_INTARO' => false,
            'UF_AGREE_PL_INTARO' => false,
            'UF_PD_PROC_PL_INTARO' => false
        ]);

        return [
            'result' => $result,
            'msg'    => GetMessage('NOT_REGISTER'),
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
                || $request['UF_AGREE_PL_INTARO'] !== 'on')
            && $customer->getLoyalty()->getIsAgreeLoyaltyProgramRules() !== 1
        ) {
            return [
                'result' => false,
                'msg'    => GetMessage('NOT_AGREE_LP_RULES'),
            ];
        }

        if ((!isset($request['UF_PD_PROC_PL_INTARO'])
            || $request['UF_PD_PROC_PL_INTARO'] !== 'on')
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
        if (isset($request['phone'])) {
            $phoneNumber = Utils::filterPhone($request['phone']);

            if (!is_numeric($phoneNumber)) {
                return [
                    'status'   => 'error',
                    'msg'      => GetMessage('PHONE_ERROR'),
                    'msgColor' => 'brown',
                ];
            }
        }

        $user = User::getEntityByPrimary($request['customerId']);

        global $USER_FIELD_MANAGER;

        $USER_FIELD_MANAGER->Update('USER', $request['customerId'], [
            'UF_CARD_NUM_INTARO' => $request['card'],
        ]);

        if (empty($user->getPersonalPhone()) && isset($request['phone'])) {
            $user->setPersonalPhone($request['phone']);
            $user->save();
        }

        //TODO когда станет известен формат карты ПЛ, то добавить валидацию ввода
        $service        = new LoyaltyAccountService();
        $createResponse = $service->createLoyaltyAccount(
            $request['phone'],
            $request['card'],
            (string) $request['customerId']
        );

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

    /**
     * @param array $allFields
     *
     * @return array
     * @throws \Intaro\RetailCrm\Component\Builder\Exception\BuilderException|\ReflectionException
     */
    public function activateAccountAction(array $allFields): array
    {
        global $USER;

        /** @var CustomerService $customerService */
        $customerService = ServiceLocator::get(CustomerService::class);
        /** @var \Intaro\RetailCrm\Component\Builder\Api\CustomerBuilder $customerBuilder */
        $customerBuilder = ServiceLocator::get(CustomerBuilder::class);

        /** @var LoyaltyAccountService $loyaltyAccountService */
        $loyaltyAccountService = ServiceLocator::get(LoyaltyAccountService::class);
        $userObject = UserRepository::getById($USER->GetID());

        if ($userObject === null) {
            return [
                'status'   => 'error',
                'msg'      => GetMessage('ERROR'),
                'msgColor' => 'brown',
            ];
        }

        $handledFields = $this->handleFields($allFields);

        $customer = $customerBuilder
            ->setCustomFields($this->getEntityFields($handledFields, 'customer'))
            ->setUser($userObject)
            ->build()
            ->getResult();

        $customerBuilder->reset();

        $editResponse = $customerService->editCustomer($customer);

        if ($editResponse === false) {
            return [
                'status'   => 'error',
                'msg'      => GetMessage('ERROR'),
                'msgColor' => 'brown',
            ];
        }

        $loyaltyAccountFields = $this->getEntityFields($handledFields, 'loyalty_account');

        if (!empty($loyaltyAccountFields)) {
            $editLoyaltyAccountResponse = $loyaltyAccountService->editLoyaltyAccount(
                $userObject->getLoyalty()->getIdInLoyalty(),
                $loyaltyAccountFields
            );

            if ($editLoyaltyAccountResponse === null || $editLoyaltyAccountResponse->success !== true) {
                {
                    return [
                        'status'   => 'error',
                        'msg'      => empty($editLoyaltyAccountResponse->errorMsg)
                            ? GetMessage('ERROR')
                            : $editLoyaltyAccountResponse->errorMsg,
                        'msgColor' => 'brown',
                    ];
                }
            }
        }

        $response = $loyaltyAccountService->activateLoyaltyAccount($userObject->getLoyalty()->getIdInLoyalty());

        //Если отметка не стоит, но аккаунт активирован на стороне CRM
        if ($response !== null && $response->errorMsg === GetMessage('ALREADY_ACTIVE')) {
            $loyaltyAccountService->setLoyaltyActivateFlag($USER->GetID());

            return ['status'   => 'activate'];
        }

        if ($response !== null && $response->loyaltyAccount->active === true) {
            $loyaltyAccountService->setLoyaltyActivateFlag($userObject->getId());

            return [
                'status'   => 'activate',
                'msg'      => GetMessage('SUCCESS_REGISTER'),
                'msgColor' => 'green',
            ];
        }

        return $loyaltyAccountService->tryActivate($userObject->getLoyalty()->getIdInLoyalty());
    }

    /**
     * @param array  $allFields
     * @param string $entityName
     *
     * @return array
     */
    private function getEntityFields(array $allFields, string $entityName): array
    {
        $externalFields = json_decode(ConfigProvider::getLoyaltyFields(), true);
        $filterResult = array_filter($externalFields, static function ($value) use ($entityName) {
            return $value['entity'] === $entityName;
        });

        if (is_array($filterResult)) {
            $codes = array_column($filterResult, 'code');

            $resultFields = array_filter($allFields, static function ($key) use ($codes) {
                return in_array($key, $codes, true);
            }, ARRAY_FILTER_USE_KEY);


            return is_array($resultFields) ? $resultFields : [];
        }

        return [];
    }

    private function handleFields(array  $allFields): array
    {
        $resultFieldsArray = [];

        foreach ($allFields as $type => $fieldsByType) {
            $resultFieldsArray = array_merge(
                $resultFieldsArray,
                $this->handleFieldByType($type, $fieldsByType)
            );
        }

        return $resultFieldsArray;
    }

    /**
     * @param string $type
     * @param array  $fields
     *
     * @return array
     * @throws \Exception
     */
    private function handleFieldByType(string $type, array $fields): array
    {
        $newFields = [];

        foreach ($fields as $field) {
            if ($type === 'checkboxes') {
                $newFields[$field['code']] = (bool) $field['value'];
                continue;
            }

            if ($type === 'numbers') {
                $newFields[$field['code']] = (int) $field['value'];
                continue;
            }

            if ($type === 'strings') {
                $newFields[$field['code']] = htmlspecialchars(trim($field['value']));
                continue;
            }

            if ($type === 'dates') {
                $newFields[$field['code']] = date('d.m.Y', strtotime($field['value']));
                continue;
            }

            if ($type === 'options') {
                $newFields[$field['code']] = htmlspecialchars(trim($field['value']));
                continue;
            }
        }

        return $newFields;
    }
}
