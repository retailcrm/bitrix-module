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

use Bitrix\Highloadblock as HL;
use Bitrix\Main\ArgumentException;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\ObjectPropertyException;
use Bitrix\Main\SystemException;
use Bitrix\Sale\BasketItemBase;
use Bitrix\Sale\Internals\OrderPropsGroupTable;
use Bitrix\Sale\Order;
use Bitrix\Sale\PropertyValue;
use Bitrix\Sale\PropertyValueCollectionBase;
use CSaleOrderProps;
use CUserTypeEntity;
use Exception;
use Intaro\RetailCrm\Component\Constants;
use Intaro\RetailCrm\Component\Factory\ClientFactory;
use Intaro\RetailCrm\Component\Handlers\EventsHandlers;
use Intaro\RetailCrm\Component\ServiceLocator;
use Intaro\RetailCrm\Model\Api\OrderProduct;
use Intaro\RetailCrm\Model\Api\Response\Order\Loyalty\OrderLoyaltyApplyResponse;
use Intaro\RetailCrm\Model\Bitrix\OrderLoyaltyData;
use Intaro\RetailCrm\Repository\OrderLoyaltyDataRepository;
use Intaro\RetailCrm\Repository\OrderPropsRepository;
use Intaro\RetailCrm\Repository\PersonTypeRepository;
use Logger;

/**
 * Class OrderLoyaltyDataService
 * @package Intaro\RetailCrm\Service
 */
class OrderLoyaltyDataService
{
    /**
     * @var \Logger
     */
    private $logger;
    
    public function __construct()
    {
        $this->logger = Logger::getInstance();
    }

    /**
     * add LP Order Props
     *
     * @throws \Bitrix\Main\ArgumentException
     * @throws \Bitrix\Main\ObjectPropertyException
     * @throws \Bitrix\Main\SystemException
     */
    public function addCustomersLoyaltyFields(): void
    {
        $persons = PersonTypeRepository::getCollectionByWhere(['ID']);
        
        foreach ($persons as $person) {
            $personId = $person->getID();
            $groupId  = $this->getGroupId($personId);
            if (isset($groupId)) {
                $this->addBonusField($personId, $groupId);
            }
        }
    }
    
    /**
     * Записывает информацию о скидках по программе лояльности в HL блок
     *
     * @param \Intaro\RetailCrm\Model\Bitrix\OrderLoyaltyData $loyaltyHl
     */
    public function addDataInLoyaltyHl(OrderLoyaltyData $loyaltyHl): void
    {
        $repository = new OrderLoyaltyDataRepository();
        
        $repository->add($loyaltyHl);
    }
    
    /**
     * Создает HL блок для хранения информации о бонусах и скидках
     *
     * @throws \Bitrix\Main\SystemException
     * @throws \Exception
     */
    public static function createLoyaltyHlBlock(): void
    {
        $result = HL\HighloadBlockTable::add([
            'NAME'       => Constants::HL_LOYALTY_CODE,
            'TABLE_NAME' => Constants::HL_LOYALTY_TABLE_NAME,
        ]);
        
        $arLangs = [
            'ru' => Loc::GetMessage('LP_ORDER_GROUP_NAME', null, 'ru'),
            'en' => Loc::GetMessage('LP_ORDER_GROUP_NAME', null, 'en'),
        ];
        
        if ($result->isSuccess()) {
            $hlId = $result->getId();
            
            foreach ($arLangs as $langKey => $langVal) {
                HL\HighloadBlockLangTable::add([
                    'ID'   => $hlId,
                    'LID'  => $langKey,
                    'NAME' => $langVal,
                ]);
            }
        } else {
            foreach ($result->getErrorMessages() as $error) {
                Logger::getInstance()->write($error);
            }
        }
        
        if (!isset($hlId)) {
            return;
        }
        
        $ufObject     = 'HLBLOCK_' . $hlId;
        $arCartFields = self::getHlFields($ufObject);
        
        foreach ($arCartFields as $arCartField) {
            $obUserField = new CUserTypeEntity();
            $obUserField->Add($arCartField);
        }
    }
    
    /**
     * Добавляет информацию о списанных бонусах и скидках программы лояльности в св-ва заказа
     *
     * @param \Bitrix\Sale\PropertyValueCollectionBase $props
     * @param float|null                               $loyaltyDiscountInput
     * @param float|null                               $loyaltyBonus
     */
    public function saveBonusAndDiscToOrderProps(
        PropertyValueCollectionBase $props,
        ?float $loyaltyDiscountInput = 0,
        ?float $loyaltyBonus = 0
    ): void {
        /** @var \Bitrix\Sale\PropertyValue $prop */
        foreach ($props as $prop) {
            if ($prop->getField('CODE') === 'LP_DISCOUNT_INFO') {
                $this->saveLpInfoToField($prop, $loyaltyDiscountInput);
            }
            
            if ($prop->getField('CODE') === 'LP_BONUS_INFO') {
                $this->saveLpInfoToField($prop, $loyaltyBonus);
            }
        }
    }
    
    /**
     * @param int $orderId
     */
    public function updateLoyaltyInfo(int $orderId): void
    {
        /** @var \Intaro\RetailCrm\Component\ApiClient\ClientAdapter $client */
        $client = ClientFactory::createClientAdapter();
        
        $response = $client->getOrder($orderId);
        
        if ($response === null) {
            return;
        }
        
        try {
            $order = Order::load($orderId);
            
            if ($order === null) {
                return;
            }
            
            $repository = new OrderLoyaltyDataRepository();
            $items      = $repository->getProductsByOrderId($orderId);
            
            $bitrixDiscounts = 0;
            $totalPrice      = 0;
            $totalBasePrice  = 0;
            
            /** @var BasketItemBase $basketItem */
            foreach ($order->getBasket() as $basketItem) {
                $totalPrice     += $basketItem->getPrice() * $basketItem->getQuantity();
                $totalBasePrice += $basketItem->getBasePrice() * $basketItem->getQuantity();
            }
            
            /** @var OrderLoyaltyData $item */
            foreach ($items as $item) {
                $bitrixDiscounts += $item->defaultDiscount * $item->quantity;
            }
            
            $loyaltyDiscount = $totalBasePrice - $totalPrice - $bitrixDiscounts;
            
            $this->saveBonusAndDiscToOrderProps(
                $order->getPropertyCollection(),
                $loyaltyDiscount ?? 0.0,
                $response->order->bonusesChargeTotal
            );
            
            EventsHandlers::$disableSaleHandler = true;
            $order->save();
            EventsHandlers::$disableSaleHandler = false;
        } catch (Exception $exception) {
            $this->logger->write($exception->getMessage(), Constants::LOYALTY_ERROR);
        }
    }
    
    /**
     * Записывает данные о ПЛ в HL-блок
     *
     * @param OrderLoyaltyData[] $loyaltyHls
     * @return void
     */
    public function saveLoyaltyInfoToHl(array $loyaltyHls): void
    {
        /** @var OrderLoyaltyDataService $hlService */
        $hlService = ServiceLocator::get(OrderLoyaltyDataService::class);
        
        foreach ($loyaltyHls as $loyaltyData) {
            $hlService->addDataInLoyaltyHl($loyaltyData);
        }
    }
    
    /**
     * Добавляет оплату бонусами в модель записи Hl-блока
     *
     * @param array              $calculateItemsInput
     * @param OrderLoyaltyData[] $loyaltyHls
     * @return array
     */
    public function addDiscountsToHl(array $calculateItemsInput, array $loyaltyHls): array
    {
        foreach ($loyaltyHls as $loyaltyHl) {
            $loyaltyHl->defaultDiscount = $calculateItemsInput[$loyaltyHl->basketItemPositionId]['SHOP_ITEM_DISCOUNT'];
        }
        
        return $loyaltyHls;
    }
    
    /**
     * Создает модели данных для HL и добавляет в них основные данные
     *
     * @param \Bitrix\Sale\Order $order
     * @return OrderLoyaltyData[]
     */
    public function addMainInfoToHl(Order $order): array
    {
        $loyaltyHls = [];
        
        try {
            /** @var BasketItemBase $basketItem */
            foreach ($order->getBasket() as $basketItem) {
                $loyaltyHl = new OrderLoyaltyData();
                
                $loyaltyHl->orderId              = $order->getId();
                $loyaltyHl->itemId               = $basketItem->getProductId();
                $loyaltyHl->basketItemPositionId = $basketItem->getId();
                $loyaltyHl->quantity             = $basketItem->getQuantity();
                $loyaltyHl->name                 = $basketItem->getField('NAME');
                
                $loyaltyHls[] = $loyaltyHl;
            }
        } catch (Exception $exception) {
            $this->logger->write($exception->getMessage(), Constants::LOYALTY_ERROR);
        }
        
        return $loyaltyHls;
    }
    
    /**
     * Добавляет оплату бонусами в модель записи Hl-блока
     *
     * @param OrderLoyaltyData[]                                                           $loyaltyHls
     * @param \Intaro\RetailCrm\Model\Api\Response\Order\Loyalty\OrderLoyaltyApplyResponse $response
     *
     * @return array
     */
    public function addBonusesToHl(
        array $loyaltyHls,
        OrderLoyaltyApplyResponse $response
    ): array {
        /** @var \Intaro\RetailCrm\Service\CookieService $service */
        $service = ServiceLocator::get(CookieService::class);
        $isDebited = false;
        $checkId   = '';
        
        //если верификация необходима, но не пройдена
        if (
            isset($response->verification, $response->verification->checkId)
            && !isset($response->verification->verifiedAt)
        ) {
            $isDebited = false;
            $service->setSmsCookie('lpOrderBonusConfirm', $response->verification);
            $checkId = $response->verification->checkId;
        }
        
        //если верификация не нужна
        if (!isset($response->verification)) {
            $isDebited = true;
        }
        
        foreach ($loyaltyHls as $key => $loyaltyHl) {
            /** @var OrderProduct $item */
            $item = $response->order->items[$key];
            
            $loyaltyHl->checkId    = $checkId;
            $loyaltyHl->isDebited  = $isDebited;
            $loyaltyHl->bonusCount = $item->bonusesChargeTotal;
        }
        
        return $loyaltyHls;
    }
    
    /**
     * @param $personId
     * @param $groupId
     */
    private function addBonusField($personId, $groupId): void
    {
        try {
            $bonusProp = OrderPropsRepository::getFirstByWhere(['ID'],
                [
                    ['PERSON_TYPE_ID', '=', $personId],
                    ['PROPS_GROUP_ID', '=', $groupId],
                ]
            );
            
            if ($bonusProp === null) {
                $fields = [
                    [
                        'REQUIRED'        => 'N',
                        'NAME'            => GetMessage('LP_BONUS_INFO'),
                        'TYPE'            => 'TEXTAREA',
                        'CODE'            => 'LP_BONUS_INFO',
                        'USER_PROPS'      => 'Y',
                        'IS_LOCATION'     => 'N',
                        'IS_LOCATION4TAX' => 'N',
                        'IS_EMAIL'        => 'N',
                        'IS_PROFILE_NAME' => 'N',
                        'IS_PAYER'        => 'N',
                        'IS_FILTERED'     => 'Y',
                        'PERSON_TYPE_ID'  => $personId,
                        'PROPS_GROUP_ID'  => $groupId,
                        'DEFAULT_VALUE'   => '',
                        'DESCRIPTION'     => GetMessage('LP_BONUS_INFO'),
                        'UTIL'            => 'Y',
                    ],
                    [
                        'REQUIRED'        => 'N',
                        'NAME'            => GetMessage('LP_DISCOUNT_INFO'),
                        'TYPE'            => 'TEXTAREA',
                        'CODE'            => 'LP_DISCOUNT_INFO',
                        'USER_PROPS'      => 'Y',
                        'IS_LOCATION'     => 'N',
                        'IS_LOCATION4TAX' => 'N',
                        'IS_EMAIL'        => 'N',
                        'IS_PROFILE_NAME' => 'N',
                        'IS_PAYER'        => 'N',
                        'IS_FILTERED'     => 'Y',
                        'PERSON_TYPE_ID'  => $personId,
                        'PROPS_GROUP_ID'  => $groupId,
                        'DEFAULT_VALUE'   => '',
                        'DESCRIPTION'     => GetMessage('LP_DISCOUNT_INFO'),
                        'UTIL'            => 'Y',
                    ],
                ];
                
                foreach ($fields as $field) {
                    CSaleOrderProps::Add($field);
                }
            }
        } catch (ObjectPropertyException | ArgumentException | SystemException $exception) {
            $this->logger->write($exception->getMessage(), Constants::LOYALTY_ERROR);
        }
    }
    
    /**
     * @param $personId
     * @return int
     */
    private function getGroupId($personId): ?int
    {
        try {
            $lpGroup = OrderPropsGroupTable::query()
                ->setSelect(['ID'])
                ->where(
                    [
                        ['PERSON_TYPE_ID', '=', $personId],
                        ['NAME', '=', GetMessage('LP_ORDER_GROUP_NAME')],
                    ]
                )
                ->fetch();
            
            if (is_array($lpGroup)) {
                return $lpGroup['ID'];
            }
            
            if ($lpGroup === false) {
                return OrderPropsGroupTable::add([
                    'PERSON_TYPE_ID' => $personId,
                    'NAME'           => GetMessage('LP_ORDER_GROUP_NAME'),
                ])->getId();
            }
        } catch (Exception $exception) {
            $this->logger->write($exception->getMessage(), Constants::LOYALTY_ERROR);
            
            return null;
        }
        
        return null;
    }
    
    /**
     * Возвращает настройки для генерации полей HL блока
     *
     * @param string $ufObject
     * @return array[]
     */
    private static function getHlFields(string $ufObject): array
    {
        return [
            'UF_ORDER_ID'     => [
                'ENTITY_ID'         => $ufObject,
                'FIELD_NAME'        => 'UF_ORDER_ID',
                'USER_TYPE_ID'      => 'integer',
                'MANDATORY'         => 'Y',
                'EDIT_FORM_LABEL'   => [
                    'ru' => Loc::GetMessage('UF_ORDER_ID', null, 'ru'),
                    'en' => Loc::GetMessage('UF_ORDER_ID', null, 'en'),
                ],
                'LIST_COLUMN_LABEL' => [
                    'ru' => Loc::GetMessage('UF_ORDER_ID', null, 'ru'),
                    'en' => Loc::GetMessage('UF_ORDER_ID', null, 'en'),
                ],
                'LIST_FILTER_LABEL' => [
                    'ru' => Loc::GetMessage('UF_ORDER_ID', null, 'ru'),
                    'en' => Loc::GetMessage('UF_ORDER_ID', null, 'en'),
                ],
            ],
            'UF_ITEM_ID'      => [
                'ENTITY_ID'         => $ufObject,
                'FIELD_NAME'        => 'UF_ITEM_ID',
                'USER_TYPE_ID'      => 'integer',
                'MANDATORY'         => 'Y',
                'EDIT_FORM_LABEL'   => [
                    'ru' => Loc::GetMessage('UF_ITEM_ID', null, 'ru'),
                    'en' => Loc::GetMessage('UF_ITEM_ID', null, 'en'),
                ],
                'LIST_COLUMN_LABEL' => [
                    'ru' => Loc::GetMessage('UF_ITEM_ID', null, 'ru'),
                    'en' => Loc::GetMessage('UF_ITEM_ID', null, 'en'),
                ],
                'LIST_FILTER_LABEL' => [
                    'ru' => Loc::GetMessage('UF_ITEM_ID', null, 'ru'),
                    'en' => Loc::GetMessage('UF_ITEM_ID', null, 'en'),
                ],
            ],
            'UF_ITEM_POS_ID'  => [
                'ENTITY_ID'         => $ufObject,
                'FIELD_NAME'        => 'UF_ITEM_POS_ID',
                'USER_TYPE_ID'      => 'integer',
                'MANDATORY'         => 'Y',
                'EDIT_FORM_LABEL'   => [
                    'ru' => Loc::GetMessage('UF_ITEM_POS_ID', null, 'ru'),
                    'en' => Loc::GetMessage('UF_ITEM_POS_ID', null, 'en'),
                ],
                'LIST_COLUMN_LABEL' => [
                    'ru' => Loc::GetMessage('UF_ITEM_POS_ID', null, 'ru'),
                    'en' => Loc::GetMessage('UF_ITEM_POS_ID', null, 'en'),
                ],
                'LIST_FILTER_LABEL' => [
                    'ru' => Loc::GetMessage('UF_ITEM_POS_ID', null, 'ru'),
                    'en' => Loc::GetMessage('UF_ITEM_POS_ID', null, 'en'),
                ],
            ],
            'UF_NAME'         => [
                'ENTITY_ID'         => $ufObject,
                'FIELD_NAME'        => 'UF_NAME',
                'USER_TYPE_ID'      => 'string',
                'MANDATORY'         => 'N',
                'EDIT_FORM_LABEL'   => [
                    'ru' => Loc::GetMessage('UF_NAME', null, 'ru'),
                    'en' => Loc::GetMessage('UF_NAME', null, 'en'),
                ],
                'LIST_COLUMN_LABEL' => [
                    'ru' => Loc::GetMessage('UF_NAME', null, 'ru'),
                    'en' => Loc::GetMessage('UF_NAME', null, 'en'),
                ],
                'LIST_FILTER_LABEL' => [
                    'ru' => Loc::GetMessage('UF_NAME', null, 'ru'),
                    'en' => Loc::GetMessage('UF_NAME', null, 'en'),
                ],
            ],
            'UF_DEF_DISCOUNT' => [
                'ENTITY_ID'         => $ufObject,
                'FIELD_NAME'        => 'UF_DEF_DISCOUNT',
                'USER_TYPE_ID'      => 'double',
                'MANDATORY'         => 'N',
                'EDIT_FORM_LABEL'   => [
                    'ru' => Loc::GetMessage('UF_DEF_DISCOUNT', null, 'ru'),
                    'en' => Loc::GetMessage('UF_DEF_DISCOUNT', null, 'en'),
                ],
                'LIST_COLUMN_LABEL' => [
                    'ru' => Loc::GetMessage('UF_DEF_DISCOUNT', null, 'ru'),
                    'en' => Loc::GetMessage('UF_DEF_DISCOUNT', null, 'en'),
                ],
                'LIST_FILTER_LABEL' => [
                    'ru' => Loc::GetMessage('UF_DEF_DISCOUNT', null, 'ru'),
                    'en' => Loc::GetMessage('UF_DEF_DISCOUNT', null, 'en'),
                ],
            ],
            'UF_CHECK_ID'     => [
                'ENTITY_ID'         => $ufObject,
                'FIELD_NAME'        => 'UF_CHECK_ID',
                'USER_TYPE_ID'      => 'string',
                'MANDATORY'         => 'N',
                'EDIT_FORM_LABEL'   => [
                    'ru' => Loc::GetMessage('UF_CHECK_ID', null, 'ru'),
                    'en' => Loc::GetMessage('UF_CHECK_ID', null, 'en'),
                ],
                'LIST_COLUMN_LABEL' => [
                    'ru' => Loc::GetMessage('UF_CHECK_ID', null, 'ru'),
                    'en' => Loc::GetMessage('UF_CHECK_ID', null, 'en'),
                ],
                'LIST_FILTER_LABEL' => [
                    'ru' => Loc::GetMessage('UF_CHECK_ID', null, 'ru'),
                    'en' => Loc::GetMessage('UF_CHECK_ID', null, 'en'),
                ],
            ],
            'UF_IS_DEBITED'   => [
                'ENTITY_ID'         => $ufObject,
                'FIELD_NAME'        => 'UF_IS_DEBITED',
                'USER_TYPE_ID'      => 'boolean',
                'MANDATORY'         => 'N',
                'EDIT_FORM_LABEL'   => [
                    'ru' => Loc::GetMessage('UF_IS_DEBITED', null, 'ru'),
                    'en' => Loc::GetMessage('UF_IS_DEBITED', null, 'en'),
                ],
                'LIST_COLUMN_LABEL' => [
                    'ru' => Loc::GetMessage('UF_IS_DEBITED', null, 'ru'),
                    'en' => Loc::GetMessage('UF_IS_DEBITED', null, 'en'),
                ],
                'LIST_FILTER_LABEL' => [
                    'ru' => Loc::GetMessage('UF_IS_DEBITED', null, 'ru'),
                    'en' => Loc::GetMessage('UF_IS_DEBITED', null, 'en'),
                ],
            ],
            'UF_QUANTITY'     => [
                'ENTITY_ID'         => $ufObject,
                'FIELD_NAME'        => 'UF_QUANTITY',
                'USER_TYPE_ID'      => 'integer',
                'MANDATORY'         => 'Y',
                'EDIT_FORM_LABEL'   => [
                    'ru' => Loc::GetMessage('UF_QUANTITY', null, 'ru'),
                    'en' => Loc::GetMessage('UF_QUANTITY', null, 'en'),
                ],
                'LIST_COLUMN_LABEL' => [
                    'ru' => Loc::GetMessage('UF_QUANTITY', null, 'ru'),
                    'en' => Loc::GetMessage('UF_QUANTITY', null, 'en'),
                ],
                'LIST_FILTER_LABEL' => [
                    'ru' => Loc::GetMessage('UF_QUANTITY', null, 'ru'),
                    'en' => Loc::GetMessage('UF_QUANTITY', null, 'en'),
                ],
            ],
            'UF_BONUS_COUNT'  => [
                'ENTITY_ID'         => $ufObject,
                'FIELD_NAME'        => 'UF_BONUS_COUNT',
                'USER_TYPE_ID'      => 'integer',
                'MANDATORY'         => 'N',
                'EDIT_FORM_LABEL'   => [
                    'ru' => Loc::GetMessage('UF_BONUS_COUNT', null, 'ru'),
                    'en' => Loc::GetMessage('UF_BONUS_COUNT', null, 'en'),
                ],
                'LIST_COLUMN_LABEL' => [
                    'ru' => Loc::GetMessage('UF_BONUS_COUNT', null, 'ru'),
                    'en' => Loc::GetMessage('UF_BONUS_COUNT', null, 'en'),
                ],
                'LIST_FILTER_LABEL' => [
                    'ru' => Loc::GetMessage('UF_BONUS_COUNT', null, 'ru'),
                    'en' => Loc::GetMessage('UF_BONUS_COUNT', null, 'en'),
                ],
            ],
        ];
    }
    
    /**
     * @param \Bitrix\Sale\PropertyValue $prop
     * @param float|null                 $loyaltyBonus
     */
    private function saveLpInfoToField(PropertyValue $prop, ?float $loyaltyBonus = 0): void
    {
        try {
            $result = $prop->setField('VALUE', (string) $loyaltyBonus);
            
            if (!$result->isSuccess()) {
                $this->logger->write($result->getErrorMessages(), Constants::LOYALTY_ERROR);
            }
        } catch (Exception $exception) {
            $this->logger->write($exception->getMessage(), Constants::LOYALTY_ERROR);
        }
    }
}
