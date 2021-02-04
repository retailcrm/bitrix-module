<?php

/**
 * PHP version 7.1
 *
 * @category Integration
 * @package  Intaro\RetailCrm\Component\Loyalty
 * @author   RetailCRM <integration@retailcrm.ru>
 * @license  MIT
 * @link     http://retailcrm.ru
 * @see      http://retailcrm.ru/docs
 */
namespace Intaro\RetailCrm\Component\Handlers;

IncludeModuleLangFile(__FILE__);

use Bitrix\Main\ArgumentException;
use Bitrix\Main\Event;
use Bitrix\Main\HttpRequest;
use Bitrix\Main\ObjectPropertyException;
use Bitrix\Main\SystemException;
use Bitrix\Sale\BasketItemBase;
use Bitrix\Sale\Order;
use Intaro\RetailCrm\Component\ConfigProvider;
use Intaro\RetailCrm\Component\ServiceLocator;
use Intaro\RetailCrm\Repository\UserRepository;
use Intaro\RetailCrm\Service\LoyaltyService;
use Intaro\RetailCrm\Service\LpUserAccountService;
use Intaro\RetailCrm\Service\CustomerService;
use RetailCrmEvent;

/**
 * Class EventsHandlers
 *
 * @package Intaro\RetailCrm\Component\Loyalty
 */
class EventsHandlers
{
    /**
     * EventsHandlers constructor.
     */
    public function __construct()
    {
        IncludeModuleLangFile(__FILE__);
    }
    
    /**
     * Обработчик события, вызываемого при обновлении еще не сохраненного заказа
     *
     * @param \Bitrix\Sale\Order       $order
     * @param array                    $arUserResult
     * @param \Bitrix\Main\HttpRequest $request
     * @param array                    $arParams
     * @param array                    $arResult
     */
    public function OnSaleComponentOrderResultPreparedHandler(Order $order,
        array $arUserResult,
        HttpRequest $request,
        array $arParams,
        array &$arResult
    ): void
    {
        if (ConfigProvider::getLoyaltyProgramStatus() === 'Y') {
            $bonusInput           = (int)$request->get('bonus-input');
            $availableBonuses     = (int)$request->get('available-bonuses');
            $chargeRate           = (int)$request->get('charge-rate');
            $loyaltyDiscountInput = (float)$request->get('loyalty-discount-input');
            $calculateItemsInput  = $request->get('calculate-items-input');
            $bonusDiscount        = $bonusInput * $chargeRate;
            
            if ($bonusInput > $availableBonuses) {
                $arResult['LOYALTY']['ERROR'] = GetMessage('BONUS_ERROR_MSG');
                
                return;
            }

            $jsDataTotal = &$arResult['JS_DATA']['TOTAL'];
            
            if (($bonusInput > 0
                && $availableBonuses > 0
                && $jsDataTotal['ORDER_TOTAL_PRICE'] >= $bonusDiscount + $loyaltyDiscountInput)
            || $loyaltyDiscountInput > 0
            ) {
                $jsDataTotal['ORDER_TOTAL_PRICE']
                    -= round($bonusDiscount + $loyaltyDiscountInput, 2);
                
                $jsDataTotal['ORDER_TOTAL_PRICE_FORMATED']
                    = number_format($jsDataTotal['ORDER_TOTAL_PRICE'], 0, ',', ' ')
                    . ' ' . GetMessage('RUB');
                
                $jsDataTotal['BONUS_PAYMENT']           = $bonusDiscount;
                $jsDataTotal['DISCOUNT_PRICE']          += $bonusDiscount + $loyaltyDiscountInput;
                $jsDataTotal['DISCOUNT_PRICE_FORMATED'] = $jsDataTotal['DISCOUNT_PRICE'] . ' ' . GetMessage('RUB');
                
                $jsDataTotal['ORDER_PRICE_FORMATED']
                    = $jsDataTotal['ORDER_PRICE'] - $loyaltyDiscountInput . ' ' . GetMessage('RUB');
                
                $oldItems = json_decode(htmlspecialchars_decode($calculateItemsInput), true);
    
    
                foreach ($arResult['JS_DATA']['GRID']['ROWS'] as $key => &$item) {
                    $item['data']['SUM_NUM'] = $oldItems[$key]['SUM_NUM'];
                    $item['data']['SUM'] = $item ['data']['SUM_NUM'] . GetMessage('RUB');
                }
                
                unset($item);
            }
        }
    }
    
    /**
     * Обработчик события, вызываемого ПОСЛЕ сохранения заказа (OnSaleOrderSaved)
     *
     * @param \Bitrix\Main\Event $event
     */
    public function OnSaleOrderSavedHandler(Event $event): void
    {
        /* @var LoyaltyService $loyaltyService */
        $loyaltyService = ServiceLocator::get(LoyaltyService::class);
        $retailCrmEvent = new RetailCrmEvent();
        
        /** @var Order $order */
        $order = $event->getParameter("ENTITY");
        
        try {
            if (
                isset($_POST['calculate-items-input'], $_POST['loyalty-discount-input'])
                && !isset($_POST['bonus-input'], $_POST['available-bonuses'])
                && $event->getParameter("IS_NEW")
                && ConfigProvider::getLoyaltyProgramStatus() === 'Y'
            ) {
                $retailCrmEvent->orderSave($order);
    
                $loyaltyService->saveBonusAndDiscountValue($order, (float) $_POST['loyalty-discount-input']);
                
                $basketItems = $order->getBasket();
                
                /** @var BasketItemBase $basketItem */
                foreach ($basketItems as $key => $basketItem) {
                    $calculateItemsInput = json_decode(htmlspecialchars_decode($_POST['calculate-items-input']), true);
                    
                    $basketItem->setField('CUSTOM_PRICE', 'Y');
                    $basketItem->setField('DISCOUNT_PRICE',
                        $basketItem->getBasePrice() - $calculateItemsInput[$basketItem->getId()]['SUM_NUM']);
                    $basketItem->setField('PRICE', $calculateItemsInput[$basketItem->getId()]['SUM_NUM']);
                }
                
                $order->save();
            }
    
            if (
                isset($_POST['bonus-input'], $_POST['available-bonuses'])
                && (int)$_POST['available-bonuses'] >= (int)$_POST['bonus-input']
                && $event->getParameter("IS_NEW")
                && ConfigProvider::getLoyaltyProgramStatus() === 'Y'
            ) {
                // TODO: Replace old call with a new one.
                $retailCrmEvent->orderSave($order);
    
                $loyaltyBonusMsg = $loyaltyService->applyBonusesInOrder($order,
                    (int)$_POST['bonus-input'],
                    isset($_POST['charge-rate']) ? htmlspecialchars(trim($_POST['charge-rate'])) : 1
                );
                
                if (isset($_POST['loyalty-discount-input'])) {
                    $loyaltyService->saveBonusAndDiscountValue($order, (float) $_POST['loyalty-discount-input'], $loyaltyBonusMsg);
                }
            }
            
            //Если пл выключена, просто отправляет заказ в CRM
            if (
                ConfigProvider::getLoyaltyProgramStatus() !== 'Y'
                || LoyaltyService::getLoyaltyPersonalStatus() !== true
            ) {
                $loyaltyService->saveBonusAndDiscountValue($order);
                // TODO: Replace old call with a new one.
                $retailCrmEvent->orderSave($order);
            }
        } catch (ObjectPropertyException | ArgumentException | SystemException | \Exception $e) {
            AddMessage2Log(GetMessage('CAN_NOT_SAVE_ORDER') . $e->getMessage());
        }
    }
    
    /**
     * Регистрирует пользователя в CRM системе после регистрации на сайте
     *
     * @param array $arFields
     * @return mixed
     * @throws \ReflectionException
     */
    public function OnAfterUserRegisterHandler(array $arFields): void
    {
        if (isset($arFields['USER_ID']) && $arFields['USER_ID'] > 0) {
            $user = UserRepository::getById($arFields['USER_ID']);
            
            if (isset($_POST['REGISTER']['PERSONAL_PHONE'])) {
                $phone = htmlspecialchars($_POST['REGISTER']['PERSONAL_PHONE']);
                
                if ($user !== null) {
                    $user->setPersonalPhone($phone);
                    $user->save();
                }
                
                $arFields['PERSONAL_PHONE'] = $phone;
            }
    
            /* @var CustomerService $customerService */
            $customerService = ServiceLocator::get(CustomerService::class);
            $customer        = $customerService->createModel($arFields['USER_ID']);
            
            $customerService->createOrUpdateCustomer($customer);

            //Если пользователь выразил желание зарегистрироваться в ПЛ и согласился со всеми правилами
            if ((int)$arFields['UF_REG_IN_PL_INTARO'] === 1
                && (int)$arFields['UF_AGREE_PL_INTARO'] === 1
                && (int)$arFields['UF_PD_PROC_PL_INTARO'] === 1
            ) {
                $phone          = $arFields['PERSONAL_PHONE'] ?? '';
                $card           = $arFields['UF_CARD_NUM_INTARO'] ?? '';
                $customerId     = (string) $arFields['USER_ID'];
                $customFields   = $arFields['UF_CSTM_FLDS_INTARO'] ?? [];
                
                /** @var LpUserAccountService $service */
                $service        = ServiceLocator::get(LpUserAccountService::class);
                $createResponse = $service->createLoyaltyAccount($phone, $card, $customerId, $customFields);

                $service->activateLpUserInBitrix($createResponse, $arFields['USER_ID']);
            }
        }
    }
}
