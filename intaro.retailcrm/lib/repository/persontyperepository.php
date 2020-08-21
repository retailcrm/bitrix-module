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

use Bitrix\Main\Type\Collection;
use Bitrix\Sale\Internals\PersonTypeTable;

/**
 * Class ToModuleRepository
 *
 * @package Intaro\RetailCrm\Repository
 */
class PersonTypeRepository extends AbstractRepository
{
    /**
     * @param array $select
     * @param array $where
     * @return \Bitrix\Main\Type\Collection|null
     * @throws \Bitrix\Main\ArgumentException
     * @throws \Bitrix\Main\ObjectPropertyException
     * @throws \Bitrix\Main\SystemException
     */
    public static function getCollectionByWhere(array $select = ['*'], array $where = []): ?Collection
    {
        return PersonTypeTable::query()
            ->setSelect($select)
            ->where($where)
            ->fetchCollection();
    }
}
