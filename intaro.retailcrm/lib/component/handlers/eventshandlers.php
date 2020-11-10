<?php

/**
 * PHP version 7.1
 *
 * @category Integration
 * @package  Intaro\RetailCrm\Component\Loyalty
 * @author   retailCRM <integration@retailcrm.ru>
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
use Bitrix\Sale\Order;
use CUser;
use Intaro\RetailCrm\Component\Builder\Api\CustomerBuilder;
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
     * @param \Bitrix\Main\Event $event
     */
    public function OnBeforeSalePaymentSetFieldHandler(Event $event): void
    {
        AddMessage2Log('OnBeforeSalePaymentSetFieldHandler work! ' . $event->getDebugInfo());
    }
    
    /**
     * @return mixed
     */
    public function OnBeforeEndBufferContentHandler()
    {
        AddMessage2Log('OnBeforeEndBufferContentHandler work! ');
    }
    
    /**
     * @param \Bitrix\Main\Event $event
     */
    public function OnSaleOrderBeforeSavedHandler(Event $event): void
    {
        AddMessage2Log('OnSaleOrderBeforeSavedHandler work! ' . $event->getDebugInfo());
    }
    
    /**
     * @param \Bitrix\Main\Event $event
     */
    public function OnSaleOrderPaidHandler(Event $event): void
    {
        AddMessage2Log('OnSaleOrderPaidHandler work! ' . $event->getDebugInfo());
    }
    
    /**
     * @param \Bitrix\Main\Event $event
     */
    public function OnSaleStatusOrderChangeHandler(Event $event): void
    {
        AddMessage2Log('OnSaleStatusOrderChangeHandler work! ' . $event->getDebugInfo());
    }
    
    /**
     * @param \Bitrix\Main\Event $event
     */
    public function OnSaleOrderCanceledHandler(Event $event): void
    {
        AddMessage2Log('OnSaleOrderCanceledHandler work! ' . $event->getDebugInfo());
    }
    
    /**
     * @param \Bitrix\Main\Event $event
     */
    public function OnSaleOrderDeletedHandler(Event $event): void
    {
        AddMessage2Log('OnSaleOrderDeletedHandler work! ' . $event->getDebugInfo());
    }
    
    /**
     * @param $arResult
     * @param $arUserResult
     * @param $arParams
     *
     * @return mixed
     */
    public function OnSaleComponentOrderOneStepProcessHandler($arResult, $arUserResult, $arParams)
    {
        AddMessage2Log('OnSaleComponentOrderOneStepProcessHandler work! ' . $arUserResult . $arParams);
        return $arResult;
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
    public function OnSaleComponentOrderResultPreparedHandler(Order $order, array $arUserResult, HttpRequest $request, array $arParams, array &$arResult): void
    {
        if (ConfigProvider::getLoyaltyProgramStatus() === 'Y') {
            $bonusInput       = (int)$request->get('bonus-input');
            $availableBonuses = (int)$request->get('available-bonuses');
    
            if ($bonusInput > $availableBonuses) {
                $arResult['LOYALTY']['ERROR'] = GetMessage('BONUS_ERROR_MSG');
                return;
            }
            
            if ($bonusInput > 0
                && $availableBonuses > 0
                && $arResult['JS_DATA']['TOTAL']['ORDER_TOTAL_PRICE'] >= $bonusInput
            ) {
                $arResult['JS_DATA']['TOTAL']['ORDER_TOTAL_PRICE']          -= $bonusInput;
                $arResult['JS_DATA']['TOTAL']['ORDER_TOTAL_PRICE_FORMATED'] = number_format($arResult['JS_DATA']['TOTAL']['ORDER_TOTAL_PRICE'], 0, ',', ' ');
                $arResult['JS_DATA']['TOTAL']['BONUS_PAYMENT']              = $bonusInput;
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
        /* @var LoyaltyService $loyaltyService*/
        $loyaltyService = ServiceLocator::get(LoyaltyService::class);
        $retailCrmEvent = new RetailCrmEvent();
        
        try {
            // TODO: Replace old call with a new one.
            $retailCrmEvent->orderSave($event);
    
            $isNew = $event->getParameter("IS_NEW");
    
            if (isset($_POST['bonus-input'], $_POST['available-bonuses'])
                && $isNew
                && (int)$_POST['available-bonuses'] >= (int)$_POST['bonus-input']
            ) {
                $loyaltyService->applyBonusesInOrder($event);
            }
        } catch (ObjectPropertyException | ArgumentException | SystemException $e) {
            AddMessage2Log(GetMessage('CAN_NOT_SAVE_ORDER') . $e->getMessage());
        }
    }
    
    /**
     * Регистрирует пользователя в CRM системе после регистрации на сайте
     *
     * @param array $arFields
     * @return mixed
     * @throws \Intaro\RetailCrm\Component\Builder\Exception\BuilderException
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
    
            $contragentsTypes = ConfigProvider::getContragentTypes();
            $key = array_search('individual', $contragentsTypes, true);
            $builder = new CustomerBuilder();
            $customer = $builder
                ->reset()
                ->setAttachDaemonCollectorId(true)
                ->setPersonTypeId($key)
                ->setUser(UserRepository::getById($arFields['USER_ID']))
                ->build()
                ->getResult();
            
        /* @var CustomerService $customerService */
        $customerService = ServiceLocator::get(CustomerService::class);
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
