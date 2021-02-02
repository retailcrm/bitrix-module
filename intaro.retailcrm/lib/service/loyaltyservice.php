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

use Bitrix\Catalog\GroupTable;
use Bitrix\Currency\CurrencyLangTable;
use Bitrix\Main\Application;
use Bitrix\Main\ArgumentException;
use Bitrix\Main\ArgumentNullException;
use Bitrix\Main\ArgumentOutOfRangeException;
use Bitrix\Main\Context;
use Bitrix\Main\Diag\Debug;
use Bitrix\Main\Loader;
use Bitrix\Main\ObjectPropertyException;
use Bitrix\Main\SystemException;
use Bitrix\Sale\BasketItemBase;
use COption;
use \DateTime;
use Bitrix\Main\Web\Cookie;
use Bitrix\Sale\Order;
use CUser;
use Exception;
use Intaro\RetailCrm\Component\ConfigProvider;
use Intaro\RetailCrm\Component\Constants;
use Intaro\RetailCrm\Component\Factory\ClientFactory;
use Intaro\RetailCrm\Component\Json\Deserializer;
use Intaro\RetailCrm\Component\Json\Serializer;
use Intaro\RetailCrm\Component\ServiceLocator;
use Intaro\RetailCrm\Model\Api\LoyaltyAccount;
use Intaro\RetailCrm\Model\Api\OrderProduct;
use Intaro\RetailCrm\Model\Api\PriceType;
use Intaro\RetailCrm\Model\Api\Request\Loyalty\Account\LoyaltyAccountRequest;
use Intaro\RetailCrm\Model\Api\Request\Loyalty\LoyaltyCalculateRequest;
use Intaro\RetailCrm\Model\Api\Request\Order\Loyalty\OrderLoyaltyApplyRequest;
use Intaro\RetailCrm\Model\Api\Response\Loyalty\LoyaltyCalculateResponse;
use Intaro\RetailCrm\Model\Api\Response\Order\Loyalty\OrderLoyaltyApplyResponse;
use Intaro\RetailCrm\Model\Api\SerializedOrder;
use Intaro\RetailCrm\Model\Api\SerializedOrderProduct;
use Intaro\RetailCrm\Model\Api\SerializedOrderProductOffer;
use Intaro\RetailCrm\Model\Api\SerializedOrderReference;
use Intaro\RetailCrm\Model\Api\SerializedRelationCustomer;
use Intaro\RetailCrm\Model\Api\SmsVerification;
use Intaro\RetailCrm\Model\Bitrix\OrderLoyaltyData;
use Intaro\RetailCrm\Model\Bitrix\SmsCookie;
use Intaro\RetailCrm\Model\Bitrix\User;
use Intaro\RetailCrm\Model\Bitrix\UserLoyaltyData;
use Intaro\RetailCrm\Repository\OrderLoyaltyDataRepository;
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
    public function sendBonusPayment(int $orderId, int $bonusCount): ?OrderLoyaltyApplyResponse
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
     * @param float $bonuses количество бонусов для списания
     * @return \Intaro\RetailCrm\Model\Api\Response\Loyalty\LoyaltyCalculateResponse|mixed|null
     */
    public function calculateBonus(array $basketItems, float $bonuses = 0): ?LoyaltyCalculateResponse
    {
        global $USER;
       
        $request                              = new LoyaltyCalculateRequest();
        $request->order                       = new SerializedOrder();
        $request->order->customer             = new SerializedRelationCustomer();
        $request->order->customer->id         = $USER->GetID();
        $request->order->customer->externalId = $USER->GetID();
        
        $request->site = $this->site;
        $request->bonuses = $bonuses;
        
        foreach ($basketItems as $item) {
            $product = new SerializedOrderProduct();
    
            $fullPrice             = $item['BASE_PRICE'] ?? $item['FULL_PRICE'];
            $product->initialPrice = $fullPrice; //цена без скидки

            if ($fullPrice>0) {
                $product->discountManualAmount = $fullPrice - $item['PRICE'];
            }
            
            $product->offer                = new SerializedOrderProductOffer();
            $product->offer->externalId    = $item['ID'];
            $product->offer->id            = $item['ID'];
            $product->offer->xmlId         = $item['XML_ID'];
            $product->quantity             = $item['QUANTITY'];
            
            $prices = COption::GetOptionString(Constants::MODULE_ID, Constants::CRM_PRICES, 0);
            
            //TODO проверить передачу этого параметра (пока непонятно как)
            if (isset($prices) && is_string($prices)) {
                $product->priceType = new PriceType();
                $serializePrice     = unserialize($prices);
        
                if (!empty($serializePrice) && isset($serializePrice[$item['PRICE_TYPE_ID']])) {
                    $product->priceType->code = $serializePrice[$item['PRICE_TYPE_ID']];
                }
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
     * Добавляет оплату бонусами в заказ Битрикс
     *
     * @param \Bitrix\Sale\Order $order
     * @param                    $bonusCount /бонусная скидка в рублях
     * @param                    $rate       /курс бонуса к валюте
     */
    public function applyBonusesInOrder(Order $order, $bonusCount, $rate): void
    {
        $orderId  = $order->getId();
        $response = $this->sendBonusPayment($orderId, $bonusCount);
        
        if ($response->success) {
            $isDebited = false;
            $checkId   = '';
            
            //если верификация необходима, но не пройдена
            if (isset($response->verification, $response->verification->checkId)
                && !isset($response->verification->verifiedAt)
            ) {
                $isDebited = false;
                $this->setSmsCookie('lpOrderBonusConfirm', $response->verification);
                $checkId = $response->verification->checkId;
            }
            
            //если верификация не нужна
            if (!isset($response->verification)) {
                $isDebited = true;
            }
            
            try {
                /** @var OrderLoyaltyDataService $hlService */
                $hlService   = ServiceLocator::get(OrderLoyaltyDataService::class);
                $basketItems = $order->getBasket();
                
                if ($basketItems === null) {
                    return;
                }

                /** @var BasketItemBase $basketItem */
                foreach ($basketItems as $key=>$basketItem) {
                    $loyaltyHl               = new OrderLoyaltyData();
    
                    /** @var OrderProduct $item */
                    $item                    = $response->order->items[$key];
                    $totalLoyaltyDiscount    = $rate * $item->bonusesChargeTotal / $basketItem->getQuantity();
                    $loyaltyHl->orderId      = $orderId;
                    $loyaltyHl->itemId       = $basketItem->getId();
                    $loyaltyHl->cashDiscount = $totalLoyaltyDiscount;
                    $loyaltyHl->bonusRate    = $rate;
                    $loyaltyHl->bonusCount   = $item->bonusesChargeTotal;
                    $loyaltyHl->isDebited    = $isDebited;
                    $loyaltyHl->checkId      = $checkId;
                    $loyaltyHl->quantity     = $basketItem->getQuantity();

                    $hlService->addDataInLoyaltyHl($loyaltyHl);
    
                    $basePrice = $basketItem->getField('BASE_PRICE');

                    $basketItem->setField('CUSTOM_PRICE', 'Y');
                    $basketItem->setField('DISCOUNT_PRICE', $item->discountTotal);
                    $basketItem->setField('PRICE', $basePrice - $item->discountTotal);
                }
                
                $order->save();
            } catch (Exception $e) {
                AddMessage2Log($e->getMessage());
            }
        } else {
            Utils::handleErrors($response);
        }
    }
    
    /**
     * @param int $idInLoyalty
     * @return null|\Intaro\RetailCrm\Model\Api\LoyaltyAccount
     */
    public function getLoyaltyAccounts(int $idInLoyalty): ?LoyaltyAccount
    {
        $request                = new LoyaltyAccountRequest();
        $request->filter->id    = $idInLoyalty;
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
     * @param int $idInLoyalty
     * @return array|string[]
     */
    public function tryActivate(int $idInLoyalty): ?array
    {
        /** @var \Intaro\RetailCrm\Service\LpUserAccountService $userService */
        $userService = ServiceLocator::get(LpUserAccountService::class);
        $smsCookie   = $this->getSmsCookie('lpRegister');
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
            $smsCookie = $this->setSmsCookie('lpRegister', $activateResponse->verification);
            
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
     * Получает десерализованное содержимое куки
     *
     * @param string $cookieName
     * @return \Intaro\RetailCrm\Model\Bitrix\SmsCookie|null
     */
    public function getSmsCookie(string $cookieName): ?SmsCookie
    {
        try {
            $application = Application::getInstance();
            
            if ($application === null) {
                return null;
            }
            
            $cookieJson = $application->getContext()->getRequest()->getCookie($cookieName);
            
            if ($cookieJson !== null) {
                return Deserializer::deserialize($cookieJson, SmsCookie::class);
            }
        } catch (SystemException | Exception $exception) {
            AddMessage2Log($exception);
        }
        
        return null;
    }
    
    /**
     * Повторно отправляет бонусную оплату
     *
     * Используется при необходимости еще раз отправить смс
     *
     * @param $orderId
     * @return \Intaro\RetailCrm\Model\Bitrix\SmsCookie|bool
     */
    public function resendBonusPayment($orderId)
    {
        $bonusCount = $this->getBonusCount($orderId);
        
        if ($bonusCount === false || $bonusCount === 0) {
            return false;
        }
        
        /** @var  OrderLoyaltyApplyResponse $response */
        $response = $this->sendBonusPayment($orderId, $bonusCount);
        
        if ($response === null || !($response instanceof OrderLoyaltyApplyResponse)) {
            return false;
        }
        
        if (isset($response->verification, $response->verification->checkId)
            && empty($response->verification->verifiedAt)
        ) {
            return $this->setSmsCookie('lpOrderBonusConfirm', $response->verification);
        }
        
        if (!empty($response->verification->verifiedAt)) {
            $this->setDebitedStatus($orderId, true);
            return true;
        }
    }
    
    /**
     * @param int  $orderId
     * @param bool $newStatus
     */
    public function setDebitedStatus(int $orderId, bool $newStatus): void
    {
        $repository = new OrderLoyaltyDataRepository();
        $products = $repository->getProductsByOrderId($orderId);
        
        if (is_array($products)) {
            /** @var OrderLoyaltyData $product */
            foreach ($products as $product){
                $product->isDebited = $newStatus;
                $repository->edit($product);
            }
        }
        
    }
    
    /**
     * @param array                                                                 $basketData
     * @param \Intaro\RetailCrm\Model\Api\Response\Loyalty\LoyaltyCalculateResponse $calculate
     * @return array
     */
    public  function calculateBasket(array $basketData, LoyaltyCalculateResponse $calculate): array
    {
        $basketData['LP_CALCULATE_SUCCESS']                  = $calculate->success;
        $basketData['TOTAL_RENDER_DATA']['WILL_BE_CREDITED'] = $calculate->order->bonusesCreditTotal;

        foreach ($calculate->calculations as $privilege) {
            if ($privilege->maximum && $privilege->creditBonuses === 0.0) {
                $basketData['TOTAL_RENDER_DATA']['LOYALTY_DISCOUNT']          = round($privilege->discount - $basketData['DISCOUNT_PRICE_ALL'], 2);
                $basketData['TOTAL_RENDER_DATA']['LOYALTY_DISCOUNT_FORMATED'] = $basketData['TOTAL_RENDER_DATA']['LOYALTY_DISCOUNT']
                    . ' ' . GetMessage($basketData['TOTAL_RENDER_DATA']['CURRENCY']);
                
                $basketData['TOTAL_RENDER_DATA']['PRICE']                     -= $basketData['TOTAL_RENDER_DATA']['LOYALTY_DISCOUNT'];//общая сумма со скидкой
                $basketData['TOTAL_RENDER_DATA']['PRICE_FORMATED']            = $basketData['TOTAL_RENDER_DATA']['PRICE']
                    . ' ' . GetMessage($basketData['TOTAL_RENDER_DATA']['CURRENCY']); //отформатированная сумма со скидкой
                $basketData['TOTAL_RENDER_DATA']['SUM_WITHOUT_VAT_FORMATED']  = $basketData['TOTAL_RENDER_DATA']['PRICE_FORMATED'];
                $basketData['allSum_FORMATED']                                = $basketData['TOTAL_RENDER_DATA']['PRICE_FORMATED'];
                $basketData['allSum_wVAT_FORMATED']                           = $basketData['TOTAL_RENDER_DATA']['PRICE_FORMATED'];
                $basketData['allSum']                                         = $basketData['TOTAL_RENDER_DATA']['PRICE'];
                $basketData['TOTAL_RENDER_DATA']['DISCOUNT_PRICE_FORMATED']   = $privilege->discount
                    . ' ' . GetMessage($basketData['TOTAL_RENDER_DATA']['CURRENCY']);
                $basketData['TOTAL_RENDER_DATA']['LOYALTY_DISCOUNT_DEFAULT']  = $basketData['DISCOUNT_PRICE_ALL']
                    . ' ' . GetMessage($basketData['TOTAL_RENDER_DATA']['CURRENCY']);
            }
        }
    
        foreach ($basketData['BASKET_ITEM_RENDER_DATA'] as $key => &$item) {
            $item['WILL_BE_CREDITED_BONUS'] = $calculate->order->items[$key]->bonusesCreditTotal;
        
            if ($calculate->order->items[$key]->bonusesCreditTotal === 0.0) {
                $item['PRICE']                  -= $calculate->order->items[$key]->discountTotal - ($item['SUM_DISCOUNT_PRICE']/$item['QUANTITY']);
                $item['SUM_PRICE']              = $item['PRICE'] * $item['QUANTITY'];
                $item['PRICE_FORMATED']     = $item['PRICE'] . ' ' . GetMessage($item['CURRENCY']);
                $item['SUM_PRICE_FORMATED'] = $item['SUM_PRICE'] . ' ' . GetMessage($item['CURRENCY']);
                $item['SHOW_DISCOUNT_PRICE'] = true;
                $item['SUM_DISCOUNT_PRICE'] = $calculate->order->items[$key]->discountTotal * $item['QUANTITY'];
                $item['SUM_DISCOUNT_PRICE_FORMATED'] = $item['SUM_DISCOUNT_PRICE'] . ' ' . GetMessage($item['CURRENCY']);
                $item['DISCOUNT_PRICE_PERCENT'] = round($item['SUM_DISCOUNT_PRICE']
                    /(($item['FULL_PRICE']*$item['QUANTITY'])/100), 0);
                $item['DISCOUNT_PRICE_PERCENT_FORMATED'] = $item['DISCOUNT_PRICE_PERCENT'] . '%';
                
                if (isset($item['COLUMN_LIST'])) {
                    foreach ($item['COLUMN_LIST'] as &$column) {
                        $column['VALUE'] = $column['CODE'] === 'DISCOUNT'
                            ? $item['DISCOUNT_PRICE_PERCENT_FORMATED'] : $column['VALUE'];
                    }
                    
                    unset($column);
                }
            }
        }
        
        unset($item);
        
        return $basketData;
    }
    
    
    /**
     * @param array                                                                 $orderArResult
     * @param \Intaro\RetailCrm\Model\Api\Response\Loyalty\LoyaltyCalculateResponse $calculate
     * @return array
     */
    public  function calculateOrderBasket(array $orderArResult, LoyaltyCalculateResponse $calculate): array
    {
        /** @var \Intaro\RetailCrm\Model\Api\LoyaltyCalculation $privilege */
        foreach ($calculate->calculations as $privilege) {
            if ($privilege->maximum) {
                $orderArResult['AVAILABLE_BONUSES'] = $privilege->maxChargeBonuses;
    
                $jsDataTotal  = &$orderArResult['JS_DATA']['TOTAL'];

                //если уровень скидочный
                if ($privilege->maximum && $privilege->discount > 0) {
                    
                    //Персональная скидка
                    $jsDataTotal['LOYALTY_DISCOUNT'] = $orderArResult['LOYALTY_DISCOUNT_INPUT']
                        = round($privilege->discount - $jsDataTotal['DISCOUNT_PRICE'], 2);
                    
                    //общая стоимость
                    $jsDataTotal['ORDER_TOTAL_PRICE'] -= $jsDataTotal['LOYALTY_DISCOUNT'];
    
                    //обычная скидка
                    $jsDataTotal['DEFAULT_DISCOUNT'] = $jsDataTotal['DISCOUNT_PRICE'];
                    
                    $jsDataTotal['ORDER_TOTAL_PRICE_FORMATED'] = round($jsDataTotal['ORDER_TOTAL_PRICE'], 2)
                        . ' ' . GetMessage($orderArResult['BASE_LANG_CURRENCY']);
                    
                    $jsDataTotal['DISCOUNT_PRICE'] += $jsDataTotal['LOYALTY_DISCOUNT'];
                    
                    $jsDataTotal['DISCOUNT_PRICE_FORMATED'] = $jsDataTotal['DISCOUNT_PRICE']
                        . ' ' . GetMessage($orderArResult['BASE_LANG_CURRENCY']);
                    
                    $jsDataTotal['ORDER_PRICE']-= $jsDataTotal['LOYALTY_DISCOUNT'];
                    
                    $jsDataTotal['ORDER_PRICE_FORMATED'] = $jsDataTotal['ORDER_PRICE']
                        . ' ' . GetMessage($orderArResult['BASE_LANG_CURRENCY']);
                     
                    $i = 0;
                    
                    foreach ($orderArResult['JS_DATA']['GRID']['ROWS'] as $key => &$item) {
                        $item['data']['SUM_NUM'] = $orderArResult['CALCULATE_ITEMS_INPUT'][$key]['SUM_NUM']
                            = $item['data']['SUM_BASE']
                            - ($calculate->order->items[$i]->discountTotal
                            * $item['data']['QUANTITY']);
                        
                        $orderArResult['CALCULATE_ITEMS_INPUT'][$key]['QUANTITY'] = $item['data']['QUANTITY'];
                        
                        $item['data']['SUM'] = $item['data']['SUM_NUM']
                            . ' ' . GetMessage($orderArResult['BASE_LANG_CURRENCY']);
    
                        $item['data']['DISCOUNT_PRICE'] = $calculate->order->items[$i]->discountTotal;
                        
                        $i++;
                    }
                
                    unset($item);
    
                    $orderArResult['CALCULATE_ITEMS_INPUT']
                        = htmlspecialchars(json_encode($orderArResult['CALCULATE_ITEMS_INPUT']));
                }
            }
        }
    
        $orderArResult['CHARGERATE']           = $calculate->loyalty->chargeRate;
        $orderArResult['TOTAL_BONUSES_COUNT']  = $calculate->order->loyaltyAccount->amount;
        $orderArResult['LP_CALCULATE_SUCCESS'] = $calculate->success;
        $orderArResult['WILL_BE_CREDITED']     = $calculate->order->bonusesCreditTotal;
    
        try {
            $currency = CurrencyLangTable::query()
                ->setSelect(['FORMAT_STRING'])
                ->where([
                    ['CURRENCY', '=', ConfigProvider::getCurrencyOrDefault()],
                    ['LID', '=', 'LANGUAGE_ID'],
                ])
                ->fetch();
        } catch (ObjectPropertyException | ArgumentException | SystemException $exception) {
            AddMessage2Log($exception->getMessage());
        }
    
        $orderArResult['BONUS_CURRENCY'] = $currency['FORMAT_STRING'];
        
        return $orderArResult;
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
     * TODO реализовать получение кастомных полей из CRM (когда это появится в API)
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
     * @return array|string[]|null
     */
    private function registerAndActivateUser(
        int $userId,
        string $userPhone,
        array $customFields,
        UserLoyaltyData $loyalty
    ): ?array {
        /* @var \Intaro\RetailCrm\Service\LpUserAccountService $service */
        $service    = ServiceLocator::get(LpUserAccountService::class);
        $phone      = $userPhone ?? '';
        $card       = $loyalty->getBonusCardNumber() ?? '';
        $customerId = (string)$userId;
        
        $createResponse = $service->createLoyaltyAccount($phone, $card, $customerId, $customFields);
        
        $service->activateLpUserInBitrix($createResponse, $userId);
        
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
    
    /**
     * @param string                                      $cookieName
     * @param \Intaro\RetailCrm\Model\Api\SmsVerification $smsVerification
     * @return \Intaro\RetailCrm\Model\Bitrix\SmsCookie
     */
    private function setSmsCookie(string $cookieName, SmsVerification $smsVerification): SmsCookie
    {
        $resendAvailable = $smsVerification->createdAt->modify('+1 minutes');
        
        $smsCookie                  = new SmsCookie();
        $smsCookie->createdAt       = $smsVerification->createdAt;
        $smsCookie->resendAvailable = $resendAvailable;
        $smsCookie->isVerified      = !empty($smsVerification->verifiedAt);
        $smsCookie->expiredAt       = $smsVerification->expiredAt;
        $smsCookie->checkId         = $smsVerification->checkId;
        
        $serializedArray = Serializer::serialize($smsCookie);
        
        $cookie = new Cookie(
            $cookieName,
            $serializedArray,
            MakeTimeStamp(
                $smsVerification->expiredAt->format('Y-m-d H:i:s'),
                "YYYY.MM.DD HH:MI:SS"
            )
        );
        
        Context::getCurrent()->getResponse()->addCookie($cookie);
        
        return $smsCookie;
    }
    
    /**
     * @param $orderId
     * @return false|float
     */
    private function getBonusCount($orderId)
    {
        $repository = new OrderLoyaltyDataRepository();
        $products = $repository->getProductsByOrderId($orderId);
        
        if ($products === null || count($products) === 0) {
            return false;
        }
        
        $bonusCount = 0;
        
        /** @var OrderLoyaltyData  $product */
        foreach ($products as $product){
            
            $bonusCount += $product->bonusCount*$product->quantity;
        }
        
        return round($bonusCount);
    }
    
    /**
     * Списаны ли бонусы в заказе
     *
     * @param $orderId
     * @return \Bitrix\Sale\Payment|false
     */
    public function isBonusDebited($orderId)
    {
        $repository = new OrderLoyaltyDataRepository();
        $products   = $repository->getProductsByOrderId($orderId);
    
        if ($products === null || count($products) === 0) {
            return null;
        }
    
        return $products[0]->isDebited;
    }
}
