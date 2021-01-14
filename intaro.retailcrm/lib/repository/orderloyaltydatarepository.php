<?php
/**
 * PHP version 7.1
 *
 * @category Integration
 * @package  Intaro\RetailCrm\Repository
 * @author   RetailCRM <integration@retailcrm.ru>
 * @license  MIT
 * @link     http://retailcrm.ru
 * @see      http://retailcrm.ru/docs
 */
namespace Intaro\RetailCrm\Repository;

use Bitrix\Main\Diag\Debug;
use Bitrix\Main\LoaderException;
use Bitrix\Main\SystemException;
use Exception;
use Intaro\RetailCrm\Component\Constants;
use Intaro\RetailCrm\Component\Json\Serializer;
use Intaro\RetailCrm\Model\Bitrix\OrderLoyaltyData;
use Intaro\RetailCrm\Service\Utils;

/**
 * Class OrderLoyaltyDataRepository
 *
 * @package Intaro\RetailCrm\Repository
 */
class OrderLoyaltyDataRepository extends AbstractRepository
{
    /**
     * @param \Intaro\RetailCrm\Model\Bitrix\OrderLoyaltyData $loyaltyHl
     * @return int|null
     */
    public function add(OrderLoyaltyData $loyaltyHl): ?int
    {
        try {
            $dataManager = Utils::getHlClassByName(Constants::HL_LOYALTY_CODE);
        
            if ($dataManager === null) {
                return null;
            }
        
           $result = $dataManager::add(Serializer::serializeArray($loyaltyHl, OrderLoyaltyData::class));
            
            if ($result->isSuccess()) {
                return $result->getId();
            }

            return null;
        } catch (LoaderException | SystemException | Exception $e) {
            AddMessage2Log($e->getMessage());
        }
        
        return null;
    }
}
