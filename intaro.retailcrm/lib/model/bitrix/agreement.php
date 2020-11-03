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
use Bitrix\Main\UserConsent\Internals\AgreementTable;
use Bitrix\Sale\FuserTable;
use Bitrix\Sale\Internals\OrderPropsTable;
use Intaro\RetailCrm\Model\Bitrix\ORM\ToModuleTable;

/**
 * Class Agreement
 *
 * @package Intaro\RetailCrm\Model\Bitrix
 *
 * @method int getId()
 * @method void setId(int $id)
 * @method string getCode()
 * @method void setCode(string $code)
 * @method DateTime getDateInsert()
 * @method void setDateInsert(DateTime $dateInsert)
 * @method bool getActive()
 * @method void setActive(bool $active)
 * @method string getName()
 * @method void setName(string $name)
 * @method string getType()
 * @method void setType(string $type)
 * @method string getLanguageId()
 * @method void setLanguageId(string $languageId)
 * @method string getDataProvider()
 * @method void setDataProvider(string $dataProvider)
 * @method string getAgreementText()
 * @method void setAgreementText(string $agreementText)
 * @method string getLabelText()
 * @method void setLabelText(string $labelText)
 * @method string getSecurityCode()
 * @method void setSecurityCode(string $securityCode)
 */
class Agreement extends AbstractModelProxy
{
    /**
     * @return \Bitrix\Main\ORM\Objectify\EntityObject|null
     * @throws \Bitrix\Main\ArgumentException
     * @throws \Bitrix\Main\SystemException
     */
    protected static function newObject(): ?EntityObject
    {
        return AgreementTable::createObject();
    }
}
