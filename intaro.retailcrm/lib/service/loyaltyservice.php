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
use Bitrix\Main\ArgumentNullException;
use Bitrix\Main\Loader;
use Bitrix\Main\LoaderException;
use Bitrix\Main\ObjectPropertyException;
use Bitrix\Main\SystemException;
use Bitrix\Sale\Order;
use CUser;
use Exception;
use Intaro\RetailCrm\Component\Factory\ClientFactory;
use Intaro\RetailCrm\Component\ServiceLocator;
use Intaro\RetailCrm\Model\Api\PriceType;
use Intaro\RetailCrm\Model\Api\Request\Loyalty\Account\LoyaltyAccountActivateRequest;
use Intaro\RetailCrm\Model\Api\Request\Loyalty\LoyaltyCalculateRequest;
use Intaro\RetailCrm\Model\Api\Request\Order\Loyalty\OrderLoyaltyApplyRequest;
use Intaro\RetailCrm\Model\Api\SerializedOrder;
use Intaro\RetailCrm\Model\Api\SerializedOrderProduct;
use Intaro\RetailCrm\Model\Api\SerializedOrderProductOffer;
use Intaro\RetailCrm\Model\Api\SerializedRelationCustomer;
use Intaro\RetailCrm\Repository\PaySystemActionRepository;

/**
 * Class LoyaltyService
 *
 * @package Intaro\RetailCrm\Service
 */
class LoyaltyService
{
    const STANDART_FIELDS = [
        'UF_REG_IN_PL_INTARO_TITLE'  => 'checkbox',
        'UF_AGREE_PL_INTARO_TITLE'   => 'checkbox',
        'UF_PD_PROC_PL_INTARO_TITLE' => 'checkbox',
        'UF_CARD_NUM_INTARO'         => 'text',
        'PERSONAL_PHONE'             => 'text',
    ];
    
    /**
     * @var \Intaro\RetailCrm\Component\ApiClient\ClientAdapter
     */
    private $client;
    
    /**
     * LoyaltyService constructor.
     */
    public function __construct()
    {
        $this->client = ClientFactory::createClientAdapter();
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
        $request                 = new OrderLoyaltyApplyRequest();
        $request->order->id      = $orderId;
        $request->order->bonuses = $bonusCount;
        
        $result = $this->client->loyaltyOrderApply($request);
        
        if (isset($result->errorMsg) && !empty($result->errorMsg)) {
            AddMessage2Log($result->errorMsg);
        }
        
        return $result;
    }
    
    /**
     * @param array $basketItems
     * @param int   $discountPrice
     * @param int   $discountPercent
     * @return \Intaro\RetailCrm\Model\Api\Response\Loyalty\LoyaltyCalculateResponse|mixed|null
     */
    public function calculateBonus(array $basketItems, int $discountPrice, int $discountPercent)
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
        
        /** @var \Intaro\RetailCrm\Component\ApiClient\ClientAdapter $client */
        $client        = ClientFactory::createClientAdapter();
        $credentials   = $client->getCredentials();
        $request->site = $credentials->sitesAvailable[0];
        
        foreach ($basketItems as $item) {
            $product = new SerializedOrderProduct();
            
            if ($item['DISCOUNT_PRICE_PERCENT'] > 0) {
                $product->discountManualPercent = $item['DISCOUNT_PRICE_PERCENT'];
            }
            
            if ($item['DISCOUNT_PRICE_PERCENT'] > 0) {
                $product->discountManualAmount = $item['DISCOUNT_PRICE'];
            }
            
            $product->initialPrice      = $item['PRICE'];
            $product->offer = new SerializedOrderProductOffer();
            $product->offer->externalId = $item['ID'];
            $product->offer->id         = $item['ID'];
            $product->offer->xmlId      = $item['XML_ID'];
            $product->quantity          = $item['QUANTITY'];
            
            try {
                $price = GroupTable::query()
                    ->setSelect(['NAME'])
                    ->where(
                        [
                            ['ID', '=', $item['PRICE_TYPE_ID']],
                        ]
                    )
                    ->fetch();
                $product->priceType = new PriceType();
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
    
    //TODO реализовать этот проверки регистрации в ПЛ
    
    /**
     * @return array
     */
    public function checkRegInLp(): array
    {
        global $USER;
        $rsUser     = CUser::GetByID($USER->GetID());
        $userFields = $rsUser->Fetch();
        $regInLp    = [];
        
        if (!$userFields) {
            return [];
        }
        
        //Изъявлял ли ранее пользователь желание участвовать в ПЛ?
        if (isset($userFields['UF_REG_IN_PL_INTARO'])
            && $userFields['UF_REG_IN_PL_INTARO'] === '1'
        ) {
            //ДА. Существует ли у него аккаунт?
            if (isset($userFields['UF_LP_ID_INTARO'])
                && !empty($userFields['UF_LP_ID_INTARO'])
            ) {
                //ДА. Активен ли его аккаунт?
                if (isset($userFields['UF_EXT_REG_PL_INTARO'])
                    && $userFields['UF_EXT_REG_PL_INTARO'] === '1'
                ) {
                    //ДА. Отображаем сообщение "Вы зарегистрированы в Программе лояльности"
                    $regInLp['msg'] = GetMessage('REG_COMPLETE');
                } else {
                    //НЕТ. Акаунт не активен
                    /** @var \Intaro\RetailCrm\Service\UserAccountService $userService */
                    $userService = ServiceLocator::get(UserAccountService::class);
                    $extFields   = $userService->getExtFields($userFields['UF_EXT_REG_PL_INTARO']);
                    
                    //Есть ли обязательные поля, которые нужно заполнить для завершения активации?
                    if (!empty($extFields)) {
                        //Да, есть незаполненные обязательные поля
                        $regInLp = [
                            'msg'  => GetMessage('ACTIVATE_YOUR_ACCOUNT'),
                            'form' => [
                                'button' => [
                                    'name'   => GetMessage('ACTIVATE'),
                                    'action' => 'activateAccount',
                                ],
                                'fields' => $extFields,
                            ],
                        ];
                    } else {
                        //НЕТ. Обязательных незаполненных полей нет. Тогда пробуем активировать аккаунт
                        
                        $activateResponse = $userService->activateLoyaltyAccount($userFields['UF_EXT_REG_PL_INTARO']);
                        
                        if ($activateResponse !== null
                            && isset($activateResponse->loyaltyAccount->active)
                            && $activateResponse->loyaltyAccount->active === true) {
                            $regInLp['msg'] = GetMessage('REG_COMPLETE');
                        }
                        
                        //нужна смс верификация
                        if (isset($activateResponse->verification, $activateResponse->verification->checkId)
                            && $activateResponse !== null
                            && !isset($activateResponse->verification->verifiedAt)
                        ) {
                            $regInLp = [
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
                    }
                }
            } else {
                //НЕТ. Выясняем, каких полей не хватает для СОЗДАНИЯ аккаунта, выводим форму
                $regInLp['msg']  = GetMessage('COMPLETE_YOUR_REGISTRATION');
                $regInLp['form'] = [
                    'button' => [
                        'name'   => GetMessage('CREATE'),
                        'action' => 'createAccount',
                    ],
                    'fields' => $userFields,
                ];
            }
            
        } else {
            //НЕТ. Отображаем форму на создание новой регистрации в ПЛ
            $regInLp['msg']  = GetMessage('INVITATION_TO_REGISTER');
            $regInLp['form'] = [
                'button' => [
                    'name'   => GetMessage('CREATE'),
                    'action' => 'createAccount',
                ],
                'fields' => $this->getStandartFields(),
            ];
        }
        return $regInLp;
    }
    
    /**
     * @param bool $userFields
     * @return array
     */
    private function getStandartFields(bool $userFields)
    {
        $resultFields = [];
        foreach (self::STANDART_FIELDS as $key => $value) {
            if ($value === 'text' && empty($userFields[$key])) {
                $resultFields[$key] = [
                    'value' => $value,
                ];
            }
            if ($value === 'checkbox' && $userFields[$key] !== '1') {
                $resultFields[$key] = [
                    'value' => $value,
                ];
            }
        }
        return $resultFields;
    }
}
