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

use Bitrix\Sale\OrderUserProperties;
use Intaro\RetailCrm\Component\Json\Deserializer;
use Intaro\RetailCrm\Component\Json\Serializer;
use Intaro\RetailCrm\Model\Bitrix\BuyerProfile;

/**
 * Class UserRepository
 *
 * @package Intaro\RetailCrm\Repository
 */
class BuyerProfileRepository extends AbstractRepository
{
    /**
     * Returns BuyerProfile by id
     *
     * @param int $id
     *
     * @return \Intaro\RetailCrm\Model\Bitrix\BuyerProfile|null
     */
    public static function getById(int $id): ?BuyerProfile
    {
        $result = OrderUserProperties::getList(['filter' => ['ID' => $id]])->fetch();

        if (!$result) {
            return null;
        }

        return static::deserialize($result);
    }

    /**
     * Returns true if provided BuyerProfile exists
     *
     * @param \Intaro\RetailCrm\Model\Bitrix\BuyerProfile $buyerProfile
     *
     * @return bool
     */
    public static function isProfileExists(BuyerProfile $buyerProfile): bool
    {
        $profileData = Serializer::serializeArray($buyerProfile);
        $found = static::findProfileByData($profileData);

        return !empty($found);
    }

    /**
     * Returns array with buyer profile if one was found. Returns empty array otherwise.
     *
     * @param array $profileData
     *
     * @return array
     */
    private static function findProfileByData(array $profileData): array
    {
        return OrderUserProperties::getList(array(
            "filter" => $profileData
        ))->fetch();
    }

    /**
     * @param array $buyerProfileData
     *
     * @return \Intaro\RetailCrm\Model\Bitrix\BuyerProfile
     */
    private static function deserialize(array $buyerProfileData): BuyerProfile
    {
        return Deserializer::deserializeArray($buyerProfileData, BuyerProfile::class);
    }
}
