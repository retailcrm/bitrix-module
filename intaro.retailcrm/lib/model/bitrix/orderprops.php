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
use Bitrix\Sale\Internals\OrderPropsTable;
use Intaro\RetailCrm\Model\Bitrix\ORM\ToModuleTable;

/**
 * Class ToModule
 *
 * @package Intaro\RetailCrm\Model\Bitrix
 *
 * @method int getId()
 * @method void setId(int $id)
 * @method int getPersonTypeId()
 * @method void setPersonTypeId(int $personTypeId)
 * @method string getName()
 * @method void setName(string $name)
 * @method string getType()
 * @method void setType(string $type)
 * @method bool getRequired()
 * @method void setRequired(bool $required)
 * @method string getDefaultValue()
 * @method void setDefaultValue(string $defaultValue)
 * @method int getSort()
 * @method void setSort(int $sort)
 * @method bool getUserProps()
 * @method void setUserProps(bool $userProps)
 * @method bool getIsLocation()
 * @method void setIsLocation(bool $isLocation)
 * @method int getPropsGroupId()
 * @method void setPropsGroupId(int $propsGroupId)
 * @method string getDescription()
 * @method void setDescription(string $description)
 * @method bool getIsEmail()
 * @method void setIsEmail(bool $isEmail)
 * @method bool getIsProfileName()
 * @method void setIsProfileName(bool $isProfileName)
 * @method bool getIsPayer()
 * @method void setIsPayer(bool $isPayer)
 * @method bool getIsLocation4tax()
 * @method void setIsLocation4tax(bool $isLocation4tax)
 * @method bool getIsFiltered()
 * @method void setIsFiltered(bool $isFiltered)
 * @method string getCode()
 * @method void setCode(string $code)
 * @method bool getIsZip()
 * @method void setIsZip(bool $isZip)
 * @method bool getIsPhone()
 * @method void setIsPhone(bool $isPhone)
 * @method bool getIsAddress()
 * @method void setIsAddress(bool $isAddress)
 * @method bool getActive()
 * @method void setActive(bool $active)
 * @method bool getUtil()
 * @method void setUtil(bool $util)
 * @method int getInputFieldLocation()
 * @method void setInputFieldLocation(int $inputFieldLocation)
 * @method bool getMultiple()
 * @method void setMultiple(bool $multiple)
 * @method string getSettings()
 * @method void setSettings(string $settings)
 * @method mixed getGroup()
 * @method void setGroup($group)
 * @method mixed getPersonType()
 * @method void setPersonType($personType)
 * @method string getEntityRegistryType()
 * @method void setEntityRegistryType(string $entityRegistryType)
 * @method string getXmlId()
 * @method void setXmlId(string $xmlId)
 */
class OrderProps extends AbstractModelProxy
{
    /**
     * @return \Bitrix\Main\ORM\Objectify\EntityObject|null
     * @throws \Bitrix\Main\ArgumentException
     * @throws \Bitrix\Main\SystemException
     */
    protected static function newObject(): ?EntityObject
    {
        return OrderPropsTable::createObject();
    }
}
