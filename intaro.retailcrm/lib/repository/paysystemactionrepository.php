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

use Bitrix\Sale\Internals\PaySystemActionTable;

/**
 * Class PaySystemActionRepository
 *
 * @package Intaro\RetailCrm\Repository
 */
class PaySystemActionRepository extends AbstractRepository
{
    /**
     * @param array $select
     * @param array $where
     * @return \Intaro\RetailCrm\Model\Bitrix\PaySystemAction|null|\Bitrix\Sale\Internals\EO_PaySystemAction
     * @throws \Bitrix\Main\ArgumentException
     * @throws \Bitrix\Main\ObjectPropertyException
     * @throws \Bitrix\Main\SystemException
     */
    public static function getFirstByWhere(array $select, array $where)
    {
        return PaySystemActionTable::query()
            ->setSelect($select)
            ->where($where)
            ->fetchObject();
    }
}
