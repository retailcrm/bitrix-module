<?php

/**
 * PHP version 7.1
 *
 * @category Integration
 * @package  Intaro\RetailCrm\Repository
 * @author   retailCRM <integration@retailcrm.ru>
 * @license  MIT
 * @link     http://retailcrm.ru
 * @see      http://retailcrm.ru/docs
 */
namespace Intaro\RetailCrm\Repository;

use Intaro\RetailCrm\Component\Json\Deserializer;
use Intaro\RetailCrm\Model\Bitrix\UserLoyaltyData;
use Intaro\RetailCrm\Model\Bitrix\ORM\UtsUserTable;

/**
 * Class UserLoyaltyDataRepository
 *
 * @package Intaro\RetailCrm\Repository
 */
class UserLoyaltyDataRepository extends AbstractRepository
{
    /**
     * @param int $userId
     * @return \Intaro\RetailCrm\Model\Bitrix\UserLoyaltyData
     * @throws \Bitrix\Main\ArgumentException
     * @throws \Bitrix\Main\ObjectPropertyException
     * @throws \Bitrix\Main\SystemException
     */
    public static function getLoyaltyFields(int $userId): UserLoyaltyData
    {
        $loyaltyFields = UtsUserTable::query()
            ->setSelect([
                'UF_CARD_NUM_INTARO',
                'UF_REG_IN_PL_INTARO',
                'UF_AGREE_PL_INTARO',
                'UF_PD_PROC_PL_INTARO',
                'UF_EXT_REG_PL_INTARO',
                'UF_REG_IN_PL_INTARO',
            ])
            ->where([['VALUE_ID', '=', $userId]])
            ->fetch();
        
        /** @var UserLoyaltyData $loyalty */
        $loyalty = Deserializer::deserializeArray($loyaltyFields, UserLoyaltyData::class);
        
        return $loyalty;
    }
}
