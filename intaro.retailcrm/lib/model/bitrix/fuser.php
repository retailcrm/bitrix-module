<?php
/**
 * PHP version 7.1
 *
 * @category Integration
 * @package  Intaro\RetailCrm\Model\Bitrix
 * @author   retailCRM <integration@retailcrm.ru>
 * @license  MIT
 * @link     http://retailcrm.ru
 * @see      http://retailcrm.ru/docs
 */
namespace Intaro\RetailCrm\Model\Bitrix;

use Bitrix\Main\ORM\Objectify\EntityObject;
use Bitrix\Main\Type\DateTime;
use Bitrix\Sale\FuserTable;

/**
 * Class Fuser
 *
 * @package Intaro\RetailCrm\Model\Bitrix
 *
 * @method int getId()
 * @method void setId(int $id)
 * @method DateTime getDateInsert()
 * @method void setDateInsert(DateTime $dateInsert)
 * @method DateTime getDateIns()
 * @method void setDateIns(DateTime $dateIns)
 * @method DateTime getDateUpdate()
 * @method void setDateUpdate(DateTime $dateUpdate)
 * @method DateTime getDateUpd()
 * @method void setDateUpd(DateTime $dateUpd)
 * @method mixed getUser()
 * @method void setUser($user)
 */
class Fuser extends AbstractModelProxy
{
    /**
     * @return \Bitrix\Main\ORM\Objectify\EntityObject|null
     * @throws \Bitrix\Main\ArgumentException
     * @throws \Bitrix\Main\SystemException
     */
    protected static function newObject(): ?EntityObject
    {
        return FuserTable::createObject();
    }
}
