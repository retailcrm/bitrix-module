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

use CUserTypeEntity;
use Exception;
use Bitrix\Highloadblock as HL;
use Intaro\RetailCrm\Model\Bitrix\LoyaltyHlBlock;
use Intaro\RetailCrm\Repository\LoyaltyHlBlockRepository;

/**
 * Class HlBlockService
 * @package Intaro\RetailCrm\Service
 */
class HlBlockService {
    
    /**
     * Записывает информацию о скидках по программе лояльности в HL блок
     *
     * @param int    $orderId
     * @param int    $bonusCount
     * @param int    $rate
     * @param bool   $isDebited
     * @param string $checkId
     * @throws \Exception
     */
    public function addDataInLoyaltyHl(int $orderId, int $bonusCount, int $rate, bool $isDebited = false, string $checkId = ''): void
    {
        $loyaltyHl               = new LoyaltyHlBlock();
        $loyaltyHl->orderId      = $orderId;
        $loyaltyHl->cashDiscount = $rate * $bonusCount;
        $loyaltyHl->bonusRate    = $rate;
        $loyaltyHl->bonusCount   = $bonusCount;
        $loyaltyHl->isDebited    = $isDebited;
        $loyaltyHl->checkId      = $checkId;

        $repository = new LoyaltyHlBlockRepository();
        
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
    
        $arLangs = Array(
            'ru' => GetMessage('LP_ORDER_GROUP_NAME'),
            'en' => GetMessage('LP_ORDER_GROUP_NAME_EN')
        );
    
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
    
        $ufObject = 'HLBLOCK_' . $hlId;
        $arCartFields = [
            'UF_ORDER_ID'       => [
                'ENTITY_ID'    => $ufObject,
                'FIELD_NAME'   => 'UF_ORDER_ID',
                'USER_TYPE_ID' => 'integer',
                'MANDATORY'    => 'Y',
                "EDIT_FORM_LABEL"   => ['ru' => GetMessage('UF_ORDER_ID')],
                "LIST_COLUMN_LABEL" => ['ru' => GetMessage('UF_ORDER_ID')],
                "LIST_FILTER_LABEL" => ['ru' => GetMessage('UF_ORDER_ID')],
            ],
            'UF_CASH_DISCOUNT'  => [
                'ENTITY_ID'    => $ufObject,
                'FIELD_NAME'   => 'UF_CASH_DISCOUNT',
                'USER_TYPE_ID' => 'integer',
                'MANDATORY'    => 'N',
                "EDIT_FORM_LABEL"   => ['ru' => GetMessage('UF_CASH_DISCOUNT')],
                "LIST_COLUMN_LABEL" => ['ru' => GetMessage('UF_CASH_DISCOUNT')],
                "LIST_FILTER_LABEL" => ['ru' => GetMessage('UF_CASH_DISCOUNT')],
            ],
            'UF_BONUS_RATE' => [
                'ENTITY_ID'    => $ufObject,
                'FIELD_NAME'   => 'UF_BONUS_RATE',
                'USER_TYPE_ID' => 'integer',
                'MANDATORY'    => 'N',
                "EDIT_FORM_LABEL"   => ['ru' => GetMessage('UF_BONUS_RATE')],
                "LIST_COLUMN_LABEL" => ['ru' => GetMessage('UF_BONUS_RATE')],
                "LIST_FILTER_LABEL" => ['ru' => GetMessage('UF_BONUS_RATE')],
            ],
            'UF_BONUS_COUNT' => [
                'ENTITY_ID'    => $ufObject,
                'FIELD_NAME'   => 'UF_BONUS_COUNT',
                'USER_TYPE_ID' => 'integer',
                'MANDATORY'    => 'N',
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
            'UF_IS_DEBITED'      => [
                'ENTITY_ID'         => $ufObject,
                'FIELD_NAME'        => 'UF_IS_DEBITED',
                'USER_TYPE_ID'      => 'boolean',
                'MANDATORY'         => 'Y',
                "EDIT_FORM_LABEL"   => ['ru' => GetMessage('UF_IS_DEBITED')],
                "LIST_COLUMN_LABEL" => ['ru' => GetMessage('UF_IS_DEBITED')],
                "LIST_FILTER_LABEL" => ['ru' => GetMessage('UF_IS_DEBITED')],
            ],
        ];
    
        foreach ($arCartFields as $arCartField) {
            $obUserField = new CUserTypeEntity();
            $obUserField->Add($arCartField);
        }
    }
}
