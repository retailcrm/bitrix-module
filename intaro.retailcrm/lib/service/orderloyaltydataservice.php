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
use Bitrix\Main\ObjectPropertyException;
use Bitrix\Main\ORM\Data\AddResult;
use Bitrix\Main\SystemException;
use Bitrix\Sale\Internals\OrderPropsGroupTable;
use CSaleOrderProps;
use CUserTypeEntity;
use Exception;
use Bitrix\Highloadblock as HL;
use Intaro\RetailCrm\Component\ConfigProvider;
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
                        "REQUIRED"        => "N",
                        "NAME"            => GetMessage('LP_BONUS_INFO'),
                        "TYPE"            => "TEXTAREA",
                        "CODE"            => "LP_BONUS_INFO",
                        "USER_PROPS"      => "Y",
                        "IS_LOCATION"     => "N",
                        "IS_LOCATION4TAX" => "N",
                        "IS_EMAIL"        => "N",
                        "IS_PROFILE_NAME" => "N",
                        "IS_PAYER"        => "N",
                        'IS_FILTERED'     => 'Y',
                        'PERSON_TYPE_ID'  => $personId,
                        'PROPS_GROUP_ID'  => $groupID,
                        "DEFAULT_VALUE"   => "",
                        "DESCRIPTION"     => GetMessage('LP_BONUS_INFO'),
                        "UTIL"            => "Y",
                    ],
                    [
                        "REQUIRED"        => "N",
                        "NAME"            => GetMessage('LP_DISCOUNT_INFO'),
                        "TYPE"            => "TEXTAREA",
                        "CODE"            => "LP_DISCOUNT_INFO",
                        "USER_PROPS"      => "Y",
                        "IS_LOCATION"     => "N",
                        "IS_LOCATION4TAX" => "N",
                        "IS_EMAIL"        => "N",
                        "IS_PROFILE_NAME" => "N",
                        "IS_PAYER"        => "N",
                        'IS_FILTERED'     => 'Y',
                        'PERSON_TYPE_ID'  => $personId,
                        'PROPS_GROUP_ID'  => $groupID,
                        "DEFAULT_VALUE"   => "",
                        "DESCRIPTION"     => GetMessage('LP_DISCOUNT_INFO'),
                        "UTIL"            => "Y",
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
            'NAME'       => 'LoyaltyProgramRetailCRM',
            'TABLE_NAME' => 'loyalty_program',
        ]);
        
        $arLangs = [
            'ru' => GetMessage('LP_ORDER_GROUP_NAME'),
            'en' => GetMessage('LP_ORDER_GROUP_NAME', null, 'en'),
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
                "EDIT_FORM_LABEL"   => ['ru' => GetMessage('UF_ORDER_ID')],
                "LIST_COLUMN_LABEL" => ['ru' => GetMessage('UF_ORDER_ID')],
                "LIST_FILTER_LABEL" => ['ru' => GetMessage('UF_ORDER_ID')],
            ],
            'UF_ITEM_ID'       => [
                'ENTITY_ID'         => $ufObject,
                'FIELD_NAME'        => 'UF_ITEM_ID',
                'USER_TYPE_ID'      => 'integer',
                'MANDATORY'         => 'Y',
                "EDIT_FORM_LABEL"   => ['ru' => GetMessage('UF_ITEM_ID')],
                "LIST_COLUMN_LABEL" => ['ru' => GetMessage('UF_ITEM_ID')],
                "LIST_FILTER_LABEL" => ['ru' => GetMessage('UF_ITEM_ID')],
            ],
            'UF_CASH_DISCOUNT' => [
                'ENTITY_ID'         => $ufObject,
                'FIELD_NAME'        => 'UF_CASH_DISCOUNT',
                'USER_TYPE_ID'      => 'integer',
                'MANDATORY'         => 'N',
                "EDIT_FORM_LABEL"   => ['ru' => GetMessage('UF_CASH_DISCOUNT')],
                "LIST_COLUMN_LABEL" => ['ru' => GetMessage('UF_CASH_DISCOUNT')],
                "LIST_FILTER_LABEL" => ['ru' => GetMessage('UF_CASH_DISCOUNT')],
            ],
            'UF_BONUS_RATE'    => [
                'ENTITY_ID'         => $ufObject,
                'FIELD_NAME'        => 'UF_BONUS_RATE',
                'USER_TYPE_ID'      => 'integer',
                'MANDATORY'         => 'N',
                "EDIT_FORM_LABEL"   => ['ru' => GetMessage('UF_BONUS_RATE')],
                "LIST_COLUMN_LABEL" => ['ru' => GetMessage('UF_BONUS_RATE')],
                "LIST_FILTER_LABEL" => ['ru' => GetMessage('UF_BONUS_RATE')],
            ],
            'UF_BONUS_COUNT'   => [
                'ENTITY_ID'         => $ufObject,
                'FIELD_NAME'        => 'UF_BONUS_COUNT',
                'USER_TYPE_ID'      => 'integer',
                'MANDATORY'         => 'N',
                "EDIT_FORM_LABEL"   => ['ru' => GetMessage('UF_BONUS_COUNT')],
                "LIST_COLUMN_LABEL" => ['ru' => GetMessage('UF_BONUS_COUNT')],
                "LIST_FILTER_LABEL" => ['ru' => GetMessage('UF_BONUS_COUNT')],
            ],
            'UF_CHECK_ID'      => [
                'ENTITY_ID'         => $ufObject,
                'FIELD_NAME'        => 'UF_CHECK_ID',
                'USER_TYPE_ID'      => 'string',
                'MANDATORY'         => 'N',
                "EDIT_FORM_LABEL"   => ['ru' => GetMessage('UF_CHECK_ID')],
                "LIST_COLUMN_LABEL" => ['ru' => GetMessage('UF_CHECK_ID')],
                "LIST_FILTER_LABEL" => ['ru' => GetMessage('UF_CHECK_ID')],
            ],
            'UF_IS_DEBITED'    => [
                'ENTITY_ID'         => $ufObject,
                'FIELD_NAME'        => 'UF_IS_DEBITED',
                'USER_TYPE_ID'      => 'boolean',
                'MANDATORY'         => 'Y',
                "EDIT_FORM_LABEL"   => ['ru' => GetMessage('UF_IS_DEBITED')],
                "LIST_COLUMN_LABEL" => ['ru' => GetMessage('UF_IS_DEBITED')],
                "LIST_FILTER_LABEL" => ['ru' => GetMessage('UF_IS_DEBITED')],
            ],
            'UF_QUANTITY'      => [
                'ENTITY_ID'         => $ufObject,
                'FIELD_NAME'        => 'UF_QUANTITY',
                'USER_TYPE_ID'      => 'integer',
                'MANDATORY'         => 'Y',
                "EDIT_FORM_LABEL"   => ['ru' => GetMessage('UF_QUANTITY')],
                "LIST_COLUMN_LABEL" => ['ru' => GetMessage('UF_QUANTITY')],
                "LIST_FILTER_LABEL" => ['ru' => GetMessage('UF_QUANTITY')],
            ],
        ];
        
        foreach ($arCartFields as $arCartField) {
            $obUserField = new CUserTypeEntity();
            $obUserField->Add($arCartField);
        }
    }
}
