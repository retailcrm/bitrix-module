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

use Bitrix\Catalog\GroupTable;
use Bitrix\Main\ArgumentException;
use Bitrix\Main\Event;
use Bitrix\Main\Loader;
use Bitrix\Main\ObjectPropertyException;
use Bitrix\Main\SystemException;
use Bitrix\Main\UserTable;
use Bitrix\Sale\PaySystem\Manager;
use CUser;
use Exception;
use Intaro\RetailCrm\Component\Factory\ClientFactory;
use Intaro\RetailCrm\Component\Json\Serializer;
use Intaro\RetailCrm\Component\ServiceLocator;
use Intaro\RetailCrm\Model\Api\LoyaltyAccount;
use Intaro\RetailCrm\Model\Api\PriceType;
use Intaro\RetailCrm\Model\Api\Request\Loyalty\Account\LoyaltyAccountRequest;
use Intaro\RetailCrm\Model\Api\Request\Loyalty\LoyaltyCalculateRequest;
use Intaro\RetailCrm\Model\Api\Request\Order\Loyalty\OrderLoyaltyApplyRequest;
use Intaro\RetailCrm\Model\Api\SerializedOrder;
use Intaro\RetailCrm\Model\Api\SerializedOrderProduct;
use Intaro\RetailCrm\Model\Api\SerializedOrderProductOffer;
use Intaro\RetailCrm\Model\Api\SerializedOrderReference;
use Intaro\RetailCrm\Model\Api\SerializedRelationCustomer;
use Intaro\RetailCrm\Model\Bitrix\User;
use Intaro\RetailCrm\Model\Bitrix\UserLoyaltyData;
use Intaro\RetailCrm\Repository\PaySystemActionRepository;
use Intaro\RetailCrm\Repository\UserRepository;

/**
 * Class LoyaltyService
 *
 * @package Intaro\RetailCrm\Service
 */
class LoyaltyService
{
    public const STANDARD_FIELDS = [
        'UF_AGREE_PL_INTARO'   => 'checkbox',
        'UF_PD_PROC_PL_INTARO' => 'checkbox',
        'PERSONAL_PHONE'       => 'text',
    ];
    
    /**
     * @var \Intaro\RetailCrm\Component\ApiClient\ClientAdapter
     */
    private $client;
    
    /**
     * @var \Intaro\RetailCrm\Model\Bitrix\User|null
     */
    private $user;
    
    /**
     * @var mixed
     */
    private $site;
    
    
    /**
     * LoyaltyService constructor.
     * @throws \Bitrix\Main\LoaderException
     */
    public function __construct()
    {
        IncludeModuleLangFile(__FILE__);
    
        $this->client = ClientFactory::createClientAdapter();
    
        $credentials = $this->client->getCredentials();
        $this->site  = $credentials->sitesAvailable[0];
        
        Loader::includeModule('Catalog');
    }
    
    /*
     * Возвращает статус пользователя в системе лояльности
     */
    public static function getLoyaltyPersonalStatus(): bool
    {
        global $USER;
        $userFields = CUser::GetByID($USER->GetID())->Fetch();
        
        return isset($userFields['UF_EXT_REG_PL_INTARO']) && $userFields['UF_EXT_REG_PL_INTARO'] === '1';
    }
    
    /**
     * @param int $orderId
     * @param int $bonusCount
     * @return \Intaro\RetailCrm\Model\Api\Response\Order\Loyalty\OrderLoyaltyApplyResponse|mixed|null
     */
    public function sendBonusPayment(int $orderId, int $bonusCount)
    {
        $request                    = new OrderLoyaltyApplyRequest();
        $request->order             = new SerializedOrderReference();
        $request->order->externalId = $orderId;
        $request->bonuses           = $bonusCount;
        $request->site              = $this->site;
        
        $result = $this->client->loyaltyOrderApply($request);
        
        if (isset($result->errorMsg) && !empty($result->errorMsg)) {
            AddMessage2Log($result->errorMsg);
        }
        
        return $result;
    }
    
    /**
     * @param array $basketItems
     * @param int   $discountPrice
     * @param float   $discountPercent
     * @return \Intaro\RetailCrm\Model\Api\Response\Loyalty\LoyaltyCalculateResponse|mixed|null
     */
    public function calculateBonus(array $basketItems, int $discountPrice, float $discountPercent)
    {
        global $USER;
        
        $request                              = new LoyaltyCalculateRequest();
        $request->order                       = new SerializedOrder();
        $request->order->customer             = new SerializedRelationCustomer();
        $request->order->customer->id         = $USER->GetID();
        $request->order->customer->externalId = $USER->GetID();
        
        if ($discountPrice > 0) {
            $request->order->discountManualAmount = $discountPrice;
        }
        
        if ($discountPercent > 0) {
            $request->order->discountManualPercent = $discountPercent;
        }
        
        $request->site = $this->site;
        
        foreach ($basketItems as $item) {
            $product = new SerializedOrderProduct();
            
            if ($item['DISCOUNT_PRICE_PERCENT'] > 0) {
                $product->discountManualPercent = $item['DISCOUNT_PRICE_PERCENT'];
            }
            
            if ($item['DISCOUNT_PRICE_PERCENT'] > 0) {
                $product->discountManualAmount = $item['DISCOUNT_PRICE'];
            }
            
            $product->initialPrice      = $item['PRICE'];
            $product->offer             = new SerializedOrderProductOffer();
            $product->offer->externalId = $item['ID'];
            $product->offer->id         = $item['ID'];
            $product->offer->xmlId      = $item['XML_ID'];
            $product->quantity          = $item['QUANTITY'];
            
            try {
                $price                    = GroupTable::query()
                    ->setSelect(['NAME'])
                    ->where(
                        [
                            ['ID', '=', $item['PRICE_TYPE_ID']],
                        ]
                    )
                    ->fetch();
                $product->priceType       = new PriceType();
                $product->priceType->code = $price['NAME'];
            } catch (ObjectPropertyException | ArgumentException | SystemException $e) {
                AddMessage2Log('GroupTable query error: ' . $e->getMessage());
            }
            $request->order->items[] = $product;
        }
        
        $result = $this->client->loyaltyCalculate($request);
        
        if (isset($result->errorMsg) && !empty($result->errorMsg)) {
            AddMessage2Log($result->errorMsg);
        }
        
        return $result;
    }
    
    //TODO доделать метод проверки регистрации в ПЛ
    
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
                /** @var \Intaro\RetailCrm\Service\LpUserAccountService $userService */
                $userService = ServiceLocator::get(LpUserAccountService::class);
                $extFields   = $userService->getExtFields($loyalty->getIdInLoyalty());
                
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
            if (empty($fields)) {
                $customFields   = $this->getExternalFields();
                $createResponse = $this->registerAndActivateUser($this->user->getId(), $this->user->getPersonalPhone(), $customFields, $loyalty);
                
                if ($createResponse === false) {
                    header('Refresh 0');
                }
    
                return ['msg' => $createResponse];
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
     * @param \Bitrix\Main\Event $event
     */
    public function applyBonusesInOrder(Event $event): void
    {
        /**@var \Bitrix\Sale\Order $order */
        $order      = $event->getParameter("ENTITY");
        $orderId    = $order->getId();
        $bonusCount = (int) $_POST['bonus-input'];
        $response   = $this->sendBonusPayment($orderId, $bonusCount);
        
        if ($response->success) {
            try {
                $bonusPaySystem    = PaySystemActionRepository::getFirstByWhere(['ID'], [['ACTION_FILE', '=', 'retailcrmbonus']]);
                $paymentCollection = $order->getPaymentCollection();

                if ($bonusPaySystem !== null) {
                    if (count($paymentCollection) === 1) {
                        /** @var \Bitrix\Sale\Payment $payment */
                        foreach ($paymentCollection as $payment) {
                            $oldSum = $payment->getField('SUM');
                            
                            $payment->setField('SUM', $oldSum - $bonusCount);
                            break;
                        }
                    }
                    
                    $service    = Manager::getObjectById($bonusPaySystem->getId());
                    $newPayment = $paymentCollection->createItem($service);
                    
                    $newPayment->setField('SUM', $bonusCount);
                    
                    //если верификация необходима, но не пройдена
                    if (isset($response->verification, $response->verification->checkId)
                        && !isset($response->verification->verifiedAt)
                    ) {
                        $newPayment->setPaid('N');
                        $newPayment->setField('COMMENTS', $response->verification->checkId);
                    }
                    
                    //если верификация не нужна
                    if (!isset($response->verification)) {
                        $newPayment->setPaid('Y');
                    }
                    
                    $order->save();
                }
            } catch (ObjectPropertyException | ArgumentException | SystemException | Exception $e) {
                AddMessage2Log('ERROR PaySystemActionRepository: ' . $e->getMessage());
            }
        }
    }
    
    /**
     * @param int $idInLoyalty
     * @return null|\Intaro\RetailCrm\Model\Api\LoyaltyAccount
     */
    public function getLoyaltyAccounts(int $idInLoyalty): ?LoyaltyAccount
    {
        $request  = new LoyaltyAccountRequest();
        $request->filter->id = $idInLoyalty;
        $request->filter->sites = $this->site;
        
        $response = $this->client->getLoyaltyAccounts($request);
        
        if ($response !== null && $response->success && isset($response->loyaltyAccounts[0])) {
            /** @var \Intaro\RetailCrm\Model\Api\LoyaltyAccount $result */
            $result = $response->loyaltyAccounts[0];

            return $result;
        }
        
        Utils::handleErrors($response);
        
        return null;
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
     * TODO реализовать получение кастомных полей из CRM
     * @return array
     */
    private function getExternalFields(): array
    {
        return [];
    }
    
    /**
     * @param int                                            $userId
     * @param string                                         $userPhone
     * @param array                                          $customFields
     * @param \Intaro\RetailCrm\Model\Bitrix\UserLoyaltyData $loyalty
     * @return mixed|string|string[]
     */
    private function registerAndActivateUser(
        int $userId,
        string $userPhone,
        array $customFields,
        UserLoyaltyData $loyalty
    ) {
        /* @var \Intaro\RetailCrm\Service\LpUserAccountService $service */
        $service    = ServiceLocator::get(LpUserAccountService::class);
        $phone      = $userPhone ?? '';
        $card       = $loyalty->getBonusCardNumber() ?? '';
        $customerId = (string) $userId;
        
        $createResponse = $service->createLoyaltyAccount($phone, $card, $customerId, $customFields);
        
        $service->activateLpUserInBitrix($createResponse, $userId);
        
        if ($createResponse !== null
            && $createResponse->success === false
            && isset($createResponse->errorMsg)
            && !empty($createResponse->errorMsg)
        ) {
            $errorDetails = '';
            
            if (isset($createResponse->errors) && is_array($createResponse->errors)) {
                $errorDetails = Utils::getResponseErrors($createResponse);
            }
            
            $msg = sprintf('%s (%s %s)', GetMessage('REGISTER_ERROR'), $createResponse->errorMsg, $errorDetails);
            
            AddMessage2Log($msg);
            
            return $msg;
        }
        
        if ($createResponse->success === true) {
            //Повторная регистрация оказалась удачной
            return GetMessage('REG_COMPLETE');
        }
    }
    
    /**
     * @param int $idInLoyalty
     * @return array|null
     */
    private function tryActivate(int $idInLoyalty): ?array
    {
        /** @var \Intaro\RetailCrm\Service\LpUserAccountService $userService */
        $userService = ServiceLocator::get(LpUserAccountService::class);
        
        //НЕТ. Обязательных незаполненных полей нет. Тогда пробуем активировать аккаунт
        $activateResponse = $userService->activateLoyaltyAccount($idInLoyalty);
    
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
            return [
                'msg'  => GetMessage('SMS_VERIFICATION'),
                'form' => [
                    'button' => [
                        'name'   => GetMessage('SEND'),
                        'action' => 'sendSmsCode',
                    ],
                    'fields' => [
                        'smsVerificationCode' => [
                            'type' => 'text',
                        ],
                        'checkId'             => [
                            'type'  => 'hidden',
                            'value' => $activateResponse->verification->checkId,
                        ],
                    ],
                ],
            ];
        }
    
        return ['msg' => GetMessage('ACTIVATE_ERROR') . ' ' . $activateResponse->errorMsg ?? ''];
    }
}
