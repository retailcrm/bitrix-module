<?php
/**
 * PHP version 7.1
 *
 * @category Integration
 * @package  Intaro\RetailCrm\Model\Api
 * @author   retailCRM <integration@retailcrm.ru>
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
use Intaro\RetailCrm\Model\Bitrix\Loyalty;
use Intaro\RetailCrm\Model\Bitrix\ORM\UtsUserTable;
use Intaro\RetailCrm\Model\Bitrix\User;

/**
 * Class UserRepository
 *
 * @package Intaro\RetailCrm\Repository
 */
class UserRepository extends AbstractRepository
{
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
            $fields['loyalty'] = self::getLoyaltyFields($fields['ID']);
        } catch (ObjectPropertyException | ArgumentException | SystemException $exception) {
            AddMessage2Log($exception->getMessage());
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
    
    /**
     * @param int $userId
     * @return \Intaro\RetailCrm\Model\Bitrix\Loyalty
     * @throws \Bitrix\Main\ArgumentException
     * @throws \Bitrix\Main\ObjectPropertyException
     * @throws \Bitrix\Main\SystemException
     */
    public static function getLoyaltyFields(int $userId): Loyalty
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
        
        /** @var Loyalty $loyalty */
        $loyalty = Deserializer::deserializeArray($loyaltyFields, Loyalty::class);
        
        return $loyalty;
    }
}
