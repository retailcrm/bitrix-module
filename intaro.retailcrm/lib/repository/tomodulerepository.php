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

use Bitrix\Main\Type\Collection;
use Intaro\RetailCrm\Model\Bitrix\ORM\ToModuleTable;
use Intaro\RetailCrm\Model\Bitrix\ToModule;

/**
 * Class ToModuleRepository
 *
 * @package Intaro\RetailCrm\Repository
 */
class ToModuleRepository extends AbstractRepository
{
    /**
     * @param array $select
     * @param array $where
     * @return \Intaro\RetailCrm\Model\Bitrix\ToModule|null
     * @throws \Bitrix\Main\ArgumentException
     * @throws \Bitrix\Main\ObjectPropertyException
     * @throws \Bitrix\Main\SystemException
     */
    public static function getFirstByWhere(array $select, array $where): ?ToModule
    {
        return ToModuleTable::query()
            ->setSelect($select)
            ->where($where)
            ->fetchObject();
    }

    /**
     * @param array $select
     * @param array $where
     *
     * @return \Bitrix\Main\Type\Collection|Intaro\RetailCrm\Model\Bitrix\ORM\EO_ToModule_Collection|null
     * @throws \Bitrix\Main\ArgumentException
     * @throws \Bitrix\Main\ObjectPropertyException
     * @throws \Bitrix\Main\SystemException
     */
    public static function getCollectionByWhere(array $select, array $where)
    {
        return ToModuleTable::query()
            ->setSelect($select)
            ->where($where)
            ->fetchCollection();
    }
}
