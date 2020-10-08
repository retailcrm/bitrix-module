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
use Bitrix\Sale\Internals\PaySystemActionTable;

/**
 * Class PaySystemAction
 *
 * @package Intaro\RetailCrm\Model\Bitrix
 *
 * @method int getId()
 * @method void setId(int $id)
 * @method int getPaySystemId()
 * @method void setPaySystemId(int $paySystemId)
 * @method int getPersonTypeId()
 * @method void setPersonTypeId(int $personTypeId)
 * @method string getName()
 * @method void setName(string $name)
 * @method string getPsaName()
 * @method void setPsaName(string $psaName)
 * @method string getCode()
 * @method void setCode(string $code)
 * @method int getSort()
 * @method void setSort(int $sort)
 * @method string getActionFile()
 * @method void setActionFile(string $actionFile)
 * @method string getResultFile()
 * @method void setResultFile(string $resultFile)
 * @method string getDescription()
 * @method void setDescription(string $description)
 * @method bool getNewWindow()
 * @method void setNewWindow(bool $newWindow)
 * @method string getParams()
 * @method void setParams(string $params)
 * @method string getTarif()
 * @method void setTarif(string $tarif)
 * @method string getPsMode()
 * @method void setPsMode(string $psMode)
 * @method bool getHavePayment()
 * @method void setHavePayment(bool $havePayment)
 * @method bool getHaveAction()
 * @method void setHaveAction(bool $haveAction)
 * @method bool getHaveResult()
 * @method void setHaveResult(bool $haveResult)
 * @method bool getHavePrepay()
 * @method void setHavePrepay(bool $havePrepay)
 * @method bool getHavePrice()
 * @method void setHavePrice(bool $havePrice)
 * @method bool getHaveResultReceive()
 * @method void setHaveResultReceive(bool $haveResultReceive)
 * @method string getEncoding()
 * @method void setEncoding(string $encoding)
 * @method int getLogotip()
 * @method void setLogotip(int $logotip)
 * @method bool getActive()
 * @method void setActive(bool $active)
 * @method bool getAllowEditPayment()
 * @method void setAllowEditPayment(bool $allowEditPayment)
 * @method string getIsCash()
 * @method void setIsCash(string $isCash)
 * @method bool getAutoChange1c()
 * @method void setAutoChange1c(bool $autoChange1c)
 * @method bool getCanPrintCheck()
 * @method void setCanPrintCheck(bool $canPrintCheck)
 * @method string getEntityRegistryType()
 * @method void setEntityRegistryType(string $entityRegistryType)
 * @method string getXmlId()
 * @method void setXmlId(string $xmlId)
 */
class PaySystemAction extends AbstractModelProxy
{
    /**
     * @return \Bitrix\Main\ORM\Objectify\EntityObject|null
     * @throws \Bitrix\Main\ArgumentException
     * @throws \Bitrix\Main\SystemException
     */
    protected static function newObject(): ?EntityObject
    {
        return PaySystemActionTable::createObject();
    }
}
