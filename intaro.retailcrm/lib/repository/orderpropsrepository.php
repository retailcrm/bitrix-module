<?php

/**
 * @category Integration
 * @package  Intaro\RetailCrm\Repository
 * @author   RetailCRM <integration@retailcrm.ru>
 * @license  MIT
 * @link     http://retailcrm.ru
 * @see      http://retailcrm.ru/docs
 */

namespace Intaro\RetailCrm\Repository;

use Bitrix\Sale\Internals\OrderPropsTable;
use Bitrix\Main\ORM\Objectify\EntityObject;
use Intaro\RetailCrm\Model\Bitrix\OrderProps;


/**
 * Class OrderPropsRepository
 * @package Intaro\RetailCrm\Repository
 */
class OrderPropsRepository extends AbstractRepository
{
    /**
     * @param array $select
     * @param array $where
     * @return \Intaro\RetailCrm\Model\Bitrix\OrderProps|null
     * @throws \Bitrix\Main\ArgumentException
     * @throws \Bitrix\Main\ObjectPropertyException
     * @throws \Bitrix\Main\SystemException
     */
    public static function getFirstByWhere(array $select, array $where): ?OrderProps
    {
        return static::getWrapped(OrderPropsTable::query()
            ->setSelect($select)
            ->where($where)
            ->fetchObject());
    }

    /**
     * @param int $id
     *
     * @return OrderProps|null
     * @throws \Bitrix\Main\ArgumentException
     * @throws \Bitrix\Main\ObjectPropertyException
     * @throws \Bitrix\Main\SystemException
     */
    public static function getById(int $id): ?OrderProps
    {
        return static::getWrapped(OrderPropsTable::getByPrimary($id)->fetchObject());
    }

    /**
     * @param \Bitrix\Main\ORM\Objectify\EntityObject|null $entityObject
     *
     * @return \Intaro\RetailCrm\Model\Bitrix\Fuser|null
     */
    private static function getWrapped(?EntityObject $entityObject): ?OrderProps
    {
        if (null === $entityObject) {
            return null;
        }

        return new OrderProps($entityObject);
    }
}
