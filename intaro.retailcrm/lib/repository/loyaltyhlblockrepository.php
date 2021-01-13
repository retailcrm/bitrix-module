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

use Bitrix\Main\LoaderException;
use Bitrix\Main\SystemException;
use Exception;
use Intaro\RetailCrm\Component\Constants;
use Intaro\RetailCrm\Component\Json\Serializer;
use Intaro\RetailCrm\Model\Bitrix\LoyaltyHlBlock;
use Intaro\RetailCrm\Service\Utils;

/**
 * Class LoyaltyHlBlockRepository
 *
 * @package Intaro\RetailCrm\Repository
 */
class LoyaltyHlBlockRepository extends AbstractRepository
{
    /**
     * @param \Intaro\RetailCrm\Model\Bitrix\LoyaltyHlBlock $loyaltyHl
     * @return int|null
     */
    public function add(LoyaltyHlBlock $loyaltyHl): ?int
    {
        try {
            $dataManager = Utils::getHlClassByName(Constants::HL_LOYALTY_CODE);
        
            if ($dataManager === null) {
                return null;
            }
        
           $result = $dataManager::add(Serializer::serializeArray($loyaltyHl, LoyaltyHlBlock::class));
            
            if ($result->isSuccess()) {
                return $result->getId();
            }

            return null;
            
        }catch (LoaderException | SystemException | Exception $e){
            AddMessage2Log($e->getMessage());
        }
    }
}
