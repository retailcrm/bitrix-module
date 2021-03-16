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
use Intaro\RetailCrm\Component\Json\Deserializer;
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
            
            $result = Serializer::serializeArray($loyaltyHl, OrderLoyaltyData::class);
            
            unset($result['ID']);
            
            $result = $dataManager::add($result);
            
            if ($result->isSuccess()) {
                return $result->getId();
            }
            
            return null;
        } catch (LoaderException | SystemException | Exception $e) {
            AddMessage2Log($e->getMessage());
        }
        
        return null;
    }
    
    /**
     * @param $orderId
     * @return array|null
     */
    public function getProductsByOrderId($orderId): ?array
    {
        try {
            $dataManager = Utils::getHlClassByName(Constants::HL_LOYALTY_CODE);
        
            if ($dataManager === null) {
                return null;
            }
        
            $products = $dataManager::query()->setSelect(['*'])->where('UF_ORDER_ID', '=', $orderId)->fetch();
        
            if ($products === false || count($products)) {
                return null;
            }
        
            $productsAr = [];
        
            foreach ($products as $product) {
                $productsAr[] = Deserializer::deserializeArray($product, OrderLoyaltyData::class);
            }
        
            return $productsAr;
        } catch (LoaderException | SystemException | Exception $e) {
            AddMessage2Log($e->getMessage());
        }
    }
    
    /**
     * @param \Intaro\RetailCrm\Model\Bitrix\OrderLoyaltyData $product
     * @return bool
     */
    public function edit(OrderLoyaltyData $product): bool
    {
        try {
            $dataManager = Utils::getHlClassByName(Constants::HL_LOYALTY_CODE);
    
            if ($dataManager === null) {
                return false;
            }
    
            $productAr = Serializer::serializeArray($product, OrderLoyaltyData::class);
            
            unset($productAr['ID']);
            
            $result = $dataManager::update($product->id, $productAr);
            
            if ($result->isSuccess()) {
                return true;
            }
            
        } catch (LoaderException | SystemException | Exception $exception) {
            AddMessage2Log($exception->getMessage());
        }
        
        return false;
    }
}
