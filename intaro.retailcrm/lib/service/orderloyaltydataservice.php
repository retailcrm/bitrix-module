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

use Bitrix\Main\ArgumentException;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\ObjectPropertyException;
use Bitrix\Main\ORM\Data\AddResult;
use Bitrix\Main\SystemException;
use Bitrix\Sale\Internals\OrderPropsGroupTable;
use CSaleOrderProps;
use CUserTypeEntity;
use Exception;
use Bitrix\Highloadblock as HL;
use Intaro\RetailCrm\Component\ConfigProvider;
use Intaro\RetailCrm\Component\Constants;
use Intaro\RetailCrm\Model\Bitrix\OrderLoyaltyData;
use Intaro\RetailCrm\Repository\OrderLoyaltyDataRepository;
use Intaro\RetailCrm\Repository\OrderPropsRepository;
use Intaro\RetailCrm\Repository\PersonTypeRepository;

/**
 * Class OrderLoyaltyDataService
 * @package Intaro\RetailCrm\Service
 */
class OrderLoyaltyDataService
{
    
    /**
     * @param $personId
     * @param $groupID
     */
    private function addBonusField($personId, $groupID): void
    {
        try {
            $bonusProp = OrderPropsRepository::getFirstByWhere(['ID'],
                [
                    ['PERSON_TYPE_ID', '=', $personId],
                    ['PROPS_GROUP_ID', '=', $groupID],
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
                        'PROPS_GROUP_ID'  => $groupID,
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
                        'PROPS_GROUP_ID'  => $groupID,
                        'DEFAULT_VALUE'   => '',
                        'DESCRIPTION'     => GetMessage('LP_DISCOUNT_INFO'),
                        'UTIL'            => 'Y',
                    ],
                ];
                
                foreach ($fields as $field) {
                    CSaleOrderProps::Add($field);
                }
            }
        } catch (ObjectPropertyException | ArgumentException | SystemException $e) {
            AddMessage2Log($e->getMessage());
        }
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
            $groupID  = $this->getGroupID($personId);
            if (isset($groupID)) {
                $this->addBonusField($personId, $groupID);
            }
        }
    }
    
    /**
     * @param $personId
     * @return int
     */
    private function getGroupID($personId): ?int
    {
        try {
            $LPGroup = OrderPropsGroupTable::query()
                ->setSelect(['ID'])
                ->where(
                    [
                        ['PERSON_TYPE_ID', '=', $personId],
                        ['NAME', '=', GetMessage('LP_ORDER_GROUP_NAME')],
                    ]
                )
                ->fetch();
            
            if (is_array($LPGroup)) {
                return $LPGroup['ID'];
            }
            
            if ($LPGroup === false) {
                return OrderPropsGroupTable::add([
                    'PERSON_TYPE_ID' => $personId,
                    'NAME'           => GetMessage('LP_ORDER_GROUP_NAME'),
                ])->getId();
            }
        } catch (Exception $e) {
            AddMessage2Log($e->getMessage());
            
            return null;
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
                AddMessage2Log($error);
            }
        }
        
        $ufObject     = 'HLBLOCK_' . $hlId;
        $arCartFields = [
            'UF_ORDER_ID'      => [
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
            'UF_ITEM_ID'       => [
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
            'UF_ITEM_POS_ID'       => [
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
            'UF_NAME'       => [
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
            'UF_DEF_DISCOUNT'       => [
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
            'UF_BONUS_CASH' => [
                'ENTITY_ID'         => $ufObject,
                'FIELD_NAME'        => 'UF_BONUS_CASH',
                'USER_TYPE_ID'      => 'integer',
                'MANDATORY'         => 'N',
                'EDIT_FORM_LABEL'   => [
                    'ru' => Loc::GetMessage('UF_BONUS_CASH', null, 'ru'),
                    'en' => Loc::GetMessage('UF_BONUS_CASH', null, 'en'),
                ],
                'LIST_COLUMN_LABEL' => [
                    'ru' => Loc::GetMessage('UF_BONUS_CASH', null, 'ru'),
                    'en' => Loc::GetMessage('UF_BONUS_CASH', null, 'en'),
                ],
                'LIST_FILTER_LABEL' => [
                    'ru' => Loc::GetMessage('UF_BONUS_CASH', null, 'ru'),
                    'en' => Loc::GetMessage('UF_BONUS_CASH', null, 'en'),
                ],
            ],
            'UF_BONUS_RATE'    => [
                'ENTITY_ID'         => $ufObject,
                'FIELD_NAME'        => 'UF_BONUS_RATE',
                'USER_TYPE_ID'      => 'integer',
                'MANDATORY'         => 'N',
                'EDIT_FORM_LABEL'   => [
                    'ru' => Loc::GetMessage('UF_BONUS_RATE', null, 'ru'),
                    'en' => Loc::GetMessage('UF_BONUS_RATE', null, 'en'),
                ],
                'LIST_COLUMN_LABEL' => [
                    'ru' => Loc::GetMessage('UF_BONUS_RATE', null, 'ru'),
                    'en' => Loc::GetMessage('UF_BONUS_RATE', null, 'en'),
                ],
                'LIST_FILTER_LABEL' => [
                    'ru' => Loc::GetMessage('UF_BONUS_RATE', null, 'ru'),
                    'en' => Loc::GetMessage('UF_BONUS_RATE', null, 'en'),
                ],
            ],
            'UF_BONUS_COUNT'   => [
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
            'UF_CHECK_ID'      => [
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
            'UF_IS_DEBITED'    => [
                'ENTITY_ID'         => $ufObject,
                'FIELD_NAME'        => 'UF_IS_DEBITED',
                'USER_TYPE_ID'      => 'boolean',
                'MANDATORY'         => 'N',
                'EDIT_FORM_LABEL' => [
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
            'UF_QUANTITY'      => [
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
        ];
        
        foreach ($arCartFields as $arCartField) {
            $obUserField = new CUserTypeEntity();
            $obUserField->Add($arCartField);
        }
    }
}
