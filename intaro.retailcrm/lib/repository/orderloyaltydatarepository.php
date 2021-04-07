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

use Bitrix\Main\ArgumentException;
use Bitrix\Main\ObjectPropertyException;
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
     * @var \Bitrix\Main\Entity\DataManager|string|null
     */
    private $dataManager;
    
    /**
     * OrderLoyaltyDataRepository constructor.
     * @throws \Bitrix\Main\LoaderException
     * @throws \Bitrix\Main\SystemException
     */
    public function __construct()
    {
        $this->dataManager = Utils::getHlClassByName(Constants::HL_LOYALTY_CODE);
    }
   
    /**
     * @param \Intaro\RetailCrm\Model\Bitrix\OrderLoyaltyData $loyaltyHl
     * @return int|null
     */
    public function add(OrderLoyaltyData $loyaltyHl): ?int
    {
        try {
            if ($this->dataManager === null) {
                return null;
            }
            
            $result = Serializer::serializeArray($loyaltyHl, OrderLoyaltyData::class);
            
            unset($result['ID']);
            
            $result = $this->dataManager::add($result);
            
            if ($result->isSuccess()) {
                return $result->getId();
            }
            
            return null;
        } catch (Exception $exception) {
            AddMessage2Log($exception->getMessage());
        }
        
        return null;
    }
    
    /**
     * @param int $positionId
     * @return \Intaro\RetailCrm\Model\Bitrix\OrderLoyaltyData|null
     */
    public function getOrderLpDataByPosition(int $positionId): ?OrderLoyaltyData
    {
        if ($this->dataManager === null) {
            return null;
        }
    
        try {
            $product = $this->dataManager::query()
                ->setSelect(['*'])
                ->where('UF_ITEM_POS_ID', '=', $positionId)
                ->fetch();
            
            /** @var OrderLoyaltyData $result */
            $result = Deserializer::deserializeArray($product, OrderLoyaltyData::class);
            
            return $result;
        } catch (ObjectPropertyException | ArgumentException | SystemException $exception) {
            AddMessage2Log($exception->getMessage());
        }
    }
    
    /**
     * @param $orderId
     * @return array|null
     */
    public function getProductsByOrderId($orderId): ?array
    {
        try {
            if ($this->dataManager === null) {
                return null;
            }
        
            $products = $this->dataManager::query()->setSelect(['*'])->where('UF_ORDER_ID', '=', $orderId)->fetch();
        
            if ($products === false || count($products)) {
                return null;
            }
        
            $result = [];
        
            foreach ($products as $product) {
                $result[] = Deserializer::deserializeArray($product, OrderLoyaltyData::class);
            }
        
            return $result;
        } catch (SystemException | Exception $exception) {
            AddMessage2Log($exception->getMessage());
        }
    }
    
    /**
     * @param \Intaro\RetailCrm\Model\Bitrix\OrderLoyaltyData $position
     * @return bool
     */
    public function edit(OrderLoyaltyData $position): bool
    {
        try {
            if ($this->dataManager === null) {
                return false;
            }
    
            $productAr = Serializer::serializeArray($position, OrderLoyaltyData::class);
            
            unset($productAr['ID']);
            
            $result = $this->dataManager::update($position->id, $productAr);
            
            if ($result->isSuccess()) {
                return true;
            }
            
        } catch (Exception $exception) {
            AddMessage2Log($exception->getMessage());
        }
        
        return false;
    }
    
    /**
     * @param int $externalId
     * @return float|null
     */
    public function getDefDiscountByProductPosition(int $externalId): ?float
    {
        try {
            if ($this->dataManager === null) {
                return null;
            }
        
            $result = $this->dataManager::query()
                ->setSelect(['UF_DEF_DISCOUNT'])
                ->where([
                    ['UF_ITEM_POS_ID', '=', $externalId]
                ])
            ->fetch();
        
            if ($result !== false) {
                return (float) $result['UF_DEF_DISCOUNT'];
            }
        
        } catch (SystemException | Exception $exception) {
            AddMessage2Log($exception->getMessage());
        }
    
        return null;
    }
}
