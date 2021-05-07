<?php

/**
 * PHP version 7.1
 *
 * @category Integration
 * @package  Intaro\RetailCrm\Repository
 * @author   RetailCRM <integration@retailcrm.ru>
 * @license  MIT
 * @link     http://retailcrm.ru
 * @see      http://retailcrm.ru/docs
 */

namespace Intaro\RetailCrm\Repository;

use Bitrix\Main\ArgumentException;
use Bitrix\Main\ObjectPropertyException;
use Bitrix\Main\SystemException;
use Bitrix\Main\UserTable;
use CUser;
use Intaro\RetailCrm\Component\Json\Deserializer;
use Intaro\RetailCrm\Component\Json\Serializer;
use Intaro\RetailCrm\Model\Bitrix\User;
use Intaro\RetailCrm\Model\Bitrix\UserLoyaltyData;
use Logger;

/**
 * Class UserRepository
 *
 * @package Intaro\RetailCrm\Repository
 */
class UserRepository extends AbstractRepository
{
    public const STANDART_LOYALTY_VALUES = [
        'UF_CARD_NUM_INTARO'   => null,
        'UF_LP_ID_INTARO'      => null,
        'UF_AGREE_PL_INTARO'   => null,
        'UF_PD_PROC_PL_INTARO' => null,
        'UF_EXT_REG_PL_INTARO' => null,
        'UF_REG_IN_PL_INTARO'  => null,
    ];
    
    /**
     * @param int $id
     *
     * @return User|null
     */
    public static function getById(int $id): ?User
    {
        $fields = CUser::GetByID($id)->Fetch();
        
        if (!$fields) {
            return null;
        }
    
        try {
            $loyaltyFields = UserLoyaltyDataRepository::getLoyaltyFields($fields['ID']);
        } catch (ObjectPropertyException | ArgumentException | SystemException $exception) {
            Logger::getInstance()->write($exception->getMessage());
        }
    
        if (isset($loyaltyFields)) {
            $fields['loyalty'] = Serializer::serializeArray($loyaltyFields, UserLoyaltyData::class);
        }else{
            $fields['loyalty'] = self::STANDART_LOYALTY_VALUES;
        }
        
        return Deserializer::deserializeArray($fields, User::class);
    }
    
    /**
     * @param array $where
     * @param array $select
     * @return mixed|null
     */
    public static function getFirstByParams(array $where, array $select)
    {
        try {
            $user = UserTable::query()
                ->setSelect($select)
                ->where($where)
                ->fetch();
        } catch (ObjectPropertyException | ArgumentException | SystemException $exception) {
            return null;
        }
        
        if (!$user) {
            return null;
        }
        
        return Deserializer::deserializeArray($user, User::class);
    }
}
