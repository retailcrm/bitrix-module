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

use Bitrix\Sale\FuserTable;
use Intaro\RetailCrm\Model\Bitrix\Fuser;
use Bitrix\Main\ORM\Objectify\EntityObject;

/**
 * Class FuserRepository
 *
 * @package Intaro\RetailCrm\Repository
 */
class FuserRepository extends AbstractRepository
{
    /**
     * @param int $id
     *
     * @return Fuser|null
     * @throws \Bitrix\Main\ArgumentException
     * @throws \Bitrix\Main\ObjectPropertyException
     * @throws \Bitrix\Main\SystemException
     */
    public static function getById(int $id): ?Fuser
    {
        return static::getWrapped(FuserTable::getByPrimary($id)->fetchObject());
    }

    /**
     * @param \Bitrix\Main\ORM\Objectify\EntityObject|null $entityObject
     *
     * @return \Intaro\RetailCrm\Model\Bitrix\Fuser|null
     */
    private static function getWrapped(?EntityObject $entityObject): ?Fuser
    {
        if (null === $entityObject) {
            return null;
        }

        return new Fuser($entityObject);
    }
}
