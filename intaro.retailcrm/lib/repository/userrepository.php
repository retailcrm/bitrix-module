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
     * @return \Bitrix\Main\ORM\Objectify\EntityObject|null
     * @throws \Bitrix\Main\ArgumentException
     * @throws \Bitrix\Main\ObjectPropertyException
     * @throws \Bitrix\Main\SystemException
     */
    public function getById(int $id): ?EntityObject
    {
        $result = (UserTable::query())
            ->setSelect($this->getEntityFields(UserTable::getEntity()))
            ->addFilter('=ID', $id)->exec();
        return $result->fetchObject();
    }
}
