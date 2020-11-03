<?php
/**
 * PHP version 7.1
 *
 * @category Integration
 * @package  Intaro\RetailCrm\Model\Repository
 * @author   retailCRM <integration@retailcrm.ru>
 * @license  MIT
 * @link     http://retailcrm.ru
 * @see      http://retailcrm.ru/docs
 */
namespace Intaro\RetailCrm\Repository;

use Bitrix\Main\UserConsent\Internals\AgreementTable;
use Bitrix\Sale\OrderUserProperties;
use Intaro\RetailCrm\Component\Json\Deserializer;
use Intaro\RetailCrm\Component\Json\Serializer;
use Intaro\RetailCrm\Model\Bitrix\Agreement;
use Intaro\RetailCrm\Model\Bitrix\BuyerProfile;
use Intaro\RetailCrm\Model\Bitrix\ORM\ToModuleTable;
use Intaro\RetailCrm\Model\Bitrix\ToModule;

/**
 * Class AgreementRepository
 *
 * @package Intaro\RetailCrm\Repository
 */
class AgreementRepository extends AbstractRepository
{
    
    /**
     * Returns array with buyer profile if one was found. Returns empty array otherwise.
     *
     * @param array $select
     * @param array $where
     * @return \Intaro\RetailCrm\Model\Bitrix\Agreement|null|boolean
     * @throws \Bitrix\Main\ArgumentException
     * @throws \Bitrix\Main\ObjectPropertyException
     * @throws \Bitrix\Main\SystemException
     */
    public static function getFirstByWhere(array $select, array $where)
    {
        return AgreementTable::query()
            ->setSelect($select)
            ->where($where)
            ->fetch();
    }
    
    /**
     * @param array $buyerProfileData
     *
     * @return \Intaro\RetailCrm\Model\Bitrix\Agreement
     */
    private static function deserialize(array $buyerProfileData): Agreement
    {
        return Deserializer::deserializeArray($buyerProfileData, Agreement::class);
    }
    
    /**
     * @param $result
     * @return string
     */
    private static function serialize($result)
    {
        return Serializer::serialize($result, Agreement::class);
    }
}
