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
use Intaro\RetailCrm\Component\Json\Deserializer;
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
        $fields = \CUser::GetByID($id)->Fetch();

        if (!$fields) {
            return null;
        }

        return Deserializer::deserializeArray($fields, User::class);
    }
    
    /**
     * @param array $where
     * @param array $select
     * @return mixed|null
     */
    public static function getFirstByParams(array $where, array $select){
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
