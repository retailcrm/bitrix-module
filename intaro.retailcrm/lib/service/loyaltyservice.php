<?php

/**
 * PHP version 7.1
 *
 * @category Integration
 * @package  Intaro\RetailCrm\Service
 * @author   retailCRM <integration@retailcrm.ru>
 * @license  MIT
 * @link     http://retailcrm.ru
 * @see      http://retailcrm.ru/docs
 */
namespace Intaro\RetailCrm\Service;

use Bitrix\Catalog\GroupTable;
use Bitrix\Main\ArgumentException;
use Bitrix\Main\ArgumentNullException;
use Bitrix\Main\Loader;
use Bitrix\Main\LoaderException;
use Bitrix\Main\ObjectPropertyException;
use Bitrix\Main\SystemException;
use Bitrix\Sale\Order;
use CUser;
use Exception;
use Intaro\RetailCrm\Component\Factory\ClientFactory;
use Intaro\RetailCrm\Model\Api\Request\Loyalty\LoyaltyCalculateRequest;
use Intaro\RetailCrm\Model\Api\Request\Order\Loyalty\OrderLoyaltyApplyRequest;
use Intaro\RetailCrm\Model\Api\SerializedOrderProduct;
use Intaro\RetailCrm\Repository\PaySystemActionRepository;

/**
 * Class LoyaltyService
 *
 * @package Intaro\RetailCrm\Service
 */
class LoyaltyService
{
    /**
     * @var \Intaro\RetailCrm\Component\ApiClient\ClientAdapter
     */
    private $client;
    
    /**
     * LoyaltyService constructor.
     */
    public function __construct()
    {
        $this->client = ClientFactory::createClientAdapter();
    }
    
    /*
     * Возвращает статус пользователя в системе лояльности
     */
    public static function getLoyaltyPersonalStatus(): bool
    {
        global $USER;
        $userFields = CUser::GetByID($USER->GetID())->Fetch();
    
        return isset($userFields['UF_EXT_REG_PL_INTARO']) && $userFields['UF_EXT_REG_PL_INTARO'] === '1';
    }
    
    /**
     * @param int $orderId
     * @param int $bonusCount
     * @return \Intaro\RetailCrm\Model\Api\Response\Order\Loyalty\OrderLoyaltyApplyResponse|mixed|null
     */
    public function sendBonusPayment(int $orderId, int $bonusCount)
    {
        $request                 = new OrderLoyaltyApplyRequest();
        $request->order->id      = $orderId;
        $request->order->bonuses = $bonusCount;
        
        $result = $this->client->loyaltyOrderApply($request);
        
        if (isset($result->errorMsg) && !empty($result->errorMsg)) {
            AddMessage2Log($result->errorMsg);
        }

        return $result;
    }
    
    /**
     * @param array $basketItems
     * @param int   $discountPrice
     * @param int   $discountPercent
     * @return \Intaro\RetailCrm\Model\Api\Response\Loyalty\LoyaltyCalculateResponse|mixed|null
     */
    public function calculateBonus(array $basketItems, int $discountPrice, int $discountPercent)
    {
        global $USER;
        
        $request                              = new LoyaltyCalculateRequest();
        $request->order->customer->id         = $USER->GetID();
        $request->order->customer->externalId = $USER->GetID();
        
        if ($discountPrice > 0) {
            $request->order->discountManualAmount = $discountPrice;
        }
        
        if ($discountPercent > 0) {
            $request->order->discountManualPercent = $discountPercent;
        }
        
        /** @var \Intaro\RetailCrm\Component\ApiClient\ClientAdapter $client*/
        $client        = ClientFactory::createClientAdapter();
        $credentials   = $client->getCredentials();
        $request->site = $credentials->sitesAvailable[0];
        
        foreach ($basketItems as $item) {
            $product = new SerializedOrderProduct();
            
            if ($item['DISCOUNT_PRICE_PERCENT'] > 0) {
                $product->discountManualPercent = $item['DISCOUNT_PRICE_PERCENT'];
            }
            
            if ($item['DISCOUNT_PRICE_PERCENT'] > 0) {
                $product->discountManualAmount = $item['DISCOUNT_PRICE'];
            }
            
            $product->initialPrice      = $item['PRICE'];
            $product->offer->externalId = $item['ID'];
            $product->offer->id         = $item['ID'];
            $product->offer->xmlId      = $item['XML_ID'];
            $product->quantity          = $item['QUANTITY'];
            
            try {
                $price = GroupTable::query()
                    ->setSelect(['NAME'])
                    ->where(
                        [
                            ['ID', '=', $item['PRICE_TYPE_ID']],
                        ]
                    )
                    ->fetch();
                
                $product->priceType->code = $price['NAME'];
            } catch (ObjectPropertyException | ArgumentException | SystemException $e) {
                AddMessage2Log('GroupTable query error: ' . $e->getMessage());
            }
            $request->order->items[] = $product;
        }
    
        $result = $this->client->loyaltyCalculate($request);
        
        if (isset($result->errorMsg) && !empty($result->errorMsg)) {
            AddMessage2Log($result->errorMsg);
        }
        
        return $result;
    }
}