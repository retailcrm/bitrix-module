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
use Intaro\RetailCrm\Model\Bitrix\ORM\ToModuleTable;

/**
 * Class ToModule
 *
 * @package Intaro\RetailCrm\Model\Bitrix
 *
 * @method int getId()
 * @method void setId(int $id)
 * @method DateTime getTimestampX()
 * @method void setTimestampX(DateTime $timestampX)
 * @method int getSort()
 * @method void setSort(int $sort)
 * @method string getFromModuleId()
 * @method void setFromModuleId(string $fromModuleId)
 * @method string getMessageId()
 * @method void setMessageId(string $messageId)
 * @method string getToModuleId()
 * @method void setToModuleId(string $toModuleId)
 * @method string getToPath()
 * @method void setToPath(string $toPath)
 * @method string getToClass()
 * @method void setToClass(string $toClass)
 * @method string getToMethod()
 * @method void setToMethod(string $toMethod)
 * @method string getToMethodArg()
 * @method void setToMethodArg(string $toMethodArg)
 * @method int getVersion()
 * @method void setVersion(int $version)
 * @method string getUniqueId()
 * @method void setUniqueId(string $uniqueId)
 */
class ToModule extends AbstractModelProxy
{
    /**
     * @return \Bitrix\Main\ORM\Objectify\EntityObject|null
     * @throws \Bitrix\Main\ArgumentException
     * @throws \Bitrix\Main\SystemException
     */
    protected static function newObject(): ?EntityObject
    {
        return ToModuleTable::createObject();
    }
}
