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

use Bitrix\Main\Event;
use Bitrix\Main\HttpRequest;
use Bitrix\Sale\Order;
use Intaro\RetailCrm\Component\Builder\Bitrix\LoyaltyDataBuilder;
use Intaro\RetailCrm\Component\ConfigProvider;
use Intaro\RetailCrm\Component\ServiceLocator;
use Intaro\RetailCrm\Model\Api\Response\Order\Loyalty\OrderLoyaltyApplyResponse;
use Intaro\RetailCrm\Repository\UserRepository;
use Intaro\RetailCrm\Service\LoyaltyService;
use Intaro\RetailCrm\Service\LoyaltyAccountService;
use Intaro\RetailCrm\Service\CustomerService;
use Intaro\RetailCrm\Service\OrderLoyaltyDataService;
use Intaro\RetailCrm\Service\Utils;
use Logger;
use RetailCrmEvent;
use Throwable;

/**
 * Class EventsHandlers
 *
 * @package Intaro\RetailCrm\Component\Loyalty
 */
class EventsHandlers
{
    public static $disableSaleHandler = false;

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
     * Модифицирует данные $arResult с учетом привилегий покупателя по Программе лояльности
     *
     * @param \Bitrix\Sale\Order       $order
     * @param array                    $arUserResult
     * @param \Bitrix\Main\HttpRequest $request
     * @param array                    $arParams
     * @param array                    $arResult
     */
    public function OnSaleComponentOrderResultPreparedHandler(
        Order $order,
        array $arUserResult,
        HttpRequest $request,
        array $arParams,
        array &$arResult
    ): void {
        if (ConfigProvider::getLoyaltyProgramStatus() === 'Y') {
            $bonusInput           = (int) $request->get('bonus-input');
            $availableBonuses     = (int) $request->get('available-bonuses');
            $chargeRate           = (int) $request->get('charge-rate');
            $loyaltyDiscountInput = (float) $request->get('loyalty-discount-input');
            $calculateItemsInput  = $request->get('calculate-items-input');
            $bonusDiscount        = $bonusInput * $chargeRate;

            if ($bonusInput > $availableBonuses) {
                $arResult['LOYALTY']['ERROR'] = GetMessage('BONUS_ERROR_MSG');

                return;
            }

            $jsDataTotal = &$arResult['JS_DATA']['TOTAL'];

            $isWriteOffAvailable = $bonusInput > 0
                && $availableBonuses > 0
                && $jsDataTotal['ORDER_TOTAL_PRICE'] >= $bonusDiscount + $loyaltyDiscountInput;

            if ($isWriteOffAvailable || $loyaltyDiscountInput > 0) {
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
                $oldItems                               = json_decode(htmlspecialchars_decode($calculateItemsInput), true);

                if ($calculateItemsInput !== null) {
                    foreach ($arResult['JS_DATA']['GRID']['ROWS'] as $key => &$item) {
                        $item['data']['SUM_NUM'] = $oldItems[$key]['SUM_NUM'];
                        $item['data']['SUM']     = $item['data']['SUM_NUM'] . GetMessage('RUB');
                    }
                }

                unset($item);
            }
        }
    }

    /**
     * Обновляет информацию о Программе лояльности в административной панели.
     * При каждом открытии заказа делает запрос к CRM и получает актуальную информацию.
     *
     * @param $items
     */
    public function OnAdminContextMenuShowHandler(&$items)
    {
        global $APPLICATION;

        if (
            $_SERVER['REQUEST_METHOD'] === 'GET'
            && $_REQUEST['ID'] > 0
            && $APPLICATION->GetCurPage() === '/bitrix/admin/sale_order_view.php'
        ) {
            /* @var OrderLoyaltyDataService $service */
            $service = ServiceLocator::get(OrderLoyaltyDataService::class);

            $service->updateLoyaltyInfo($_REQUEST['ID']);
        }
    }

    /**
     * Обработчик события, вызываемого ПОСЛЕ сохранения заказа (OnSaleOrderSaved)
     *
     * @param \Bitrix\Main\Event $event
     */
    public function OnSaleOrderSavedHandler(Event $event): void
    {
        if (self::$disableSaleHandler === true) {
            return;
        }

        try {
            /* @var LoyaltyService $loyaltyService */
            $loyaltyService = ServiceLocator::get(LoyaltyService::class);

            /* @var OrderLoyaltyDataService $orderLoyaltyDataService */
            $orderLoyaltyDataService = ServiceLocator::get(OrderLoyaltyDataService::class);
            /** @var Order $order */
            $order = $event->getParameter('ENTITY');

            // TODO: Replace old call with a new one.
            $saveResult = RetailCrmEvent::orderSave($order);

            Utils::handleApiErrors($saveResult);

            $isBonusInput = (
                !empty($_POST['bonus-input'])
                && !empty($_POST['available-bonuses'])
            );
            /** @var bool $isNewOrder */
            $isNewOrder                 = $event->getParameter('IS_NEW');
            $isLoyaltyOn                = ConfigProvider::getLoyaltyProgramStatus() === 'Y';
            $isDataForLoyaltyDiscount   = isset($_POST['calculate-items-input'], $_POST['loyalty-discount-input']);
            $isBonusesIssetAndAvailable = $isBonusInput
                && (int) $_POST['available-bonuses'] >= (int) $_POST['bonus-input'];

            /** @var array $calculateItemsInput */
            $calculateItemsInput = $isDataForLoyaltyDiscount
                ? json_decode(htmlspecialchars_decode($_POST['calculate-items-input']), true)
                : [];

            if ($isNewOrder && $isLoyaltyOn) {
                self::$disableSaleHandler = true;

                $hlInfoBuilder = new LoyaltyDataBuilder();
                $hlInfoBuilder->setOrder($order);

                $discountInput            = isset($_POST['loyalty-discount-input'])
                    ? (float) $_POST['loyalty-discount-input']
                    : 0;

                $loyaltyBonusMsg = 0;
                $applyBonusResponse = null;

                //Если есть бонусы
                if ($isBonusesIssetAndAvailable) {
                    $applyBonusResponse = $loyaltyService->applyBonusesInOrder($order, (int) $_POST['bonus-input']);

                    $hlInfoBuilder->setApplyResponse($applyBonusResponse);
                    $loyaltyBonusMsg = (int) $_POST['bonus-input'];
                    $hlInfoBuilder->setBonusInputTotal((int) $_POST['bonus-input']);
                }

                //Если бонусов нет, но скидка по ПЛ есть
                if (
                    ($isDataForLoyaltyDiscount && !$isBonusInput)
                    || ($applyBonusResponse instanceof OrderLoyaltyApplyResponse && !$applyBonusResponse->order)
                ) {
                    $loyaltyService->saveDiscounts($order, $calculateItemsInput);
                }

                $orderLoyaltyDataService->saveBonusAndDiscToOrderProps(
                    $order->getPropertyCollection(),
                    $discountInput,
                    $loyaltyBonusMsg
                );
                $hlInfoBuilder->setCalculateItemsInput($calculateItemsInput);
                $orderLoyaltyDataService->saveLoyaltyInfoToHl($hlInfoBuilder->build()->getResult());

                self::$disableSaleHandler = false;
            }
        } catch (Throwable $exception) {
            Logger::getInstance()->write(GetMessage('CAN_NOT_SAVE_ORDER') . $exception->getMessage(), 'uploadApiErrors');
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
            if ((int) $arFields['UF_REG_IN_PL_INTARO'] === 1
                && (int) $arFields['UF_AGREE_PL_INTARO'] === 1
                && (int) $arFields['UF_PD_PROC_PL_INTARO'] === 1
            ) {
                $phone          = $arFields['PERSONAL_PHONE'] ?? '';
                $card           = $arFields['UF_CARD_NUM_INTARO'] ?? '';
                $customerId     = (string) $arFields['USER_ID'];

                /** @var LoyaltyAccountService $service */
                $service        = ServiceLocator::get(LoyaltyAccountService::class);
                $createResponse = $service->createLoyaltyAccount($phone, $card, $customerId);

                $service->activateLpUserInBitrix($createResponse, $arFields['USER_ID']);
            }
        }
    }
}
