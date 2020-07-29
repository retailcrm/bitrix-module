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

use Bitrix\Main\ORM\Objectify\EntityObject;
use Bitrix\Main\UserTable;
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
     * @throws \Bitrix\Main\ArgumentException
     * @throws \Bitrix\Main\ObjectPropertyException
     * @throws \Bitrix\Main\SystemException
     */
    public static function getById(int $id): ?User
    {
        return static::getWrapped(UserTable::getByPrimary($id)->fetchObject());
    }

    /**
     * @param \Bitrix\Main\ORM\Objectify\EntityObject|null $entityObject
     *
     * @return \Intaro\RetailCrm\Model\Bitrix\User|null
     */
    private static function getWrapped(?EntityObject $entityObject): ?User
    {
        if (null === $entityObject) {
            return null;
        }

        return new User($entityObject);
    }
}
