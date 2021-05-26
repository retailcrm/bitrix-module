<?php

/**
 * PHP version 7.1
 *
 * @category Integration
 * @package  Intaro\RetailCrm\Service
 * @author   RetailCRM <integration@retailcrm.ru>
 * @license  MIT
 * @link     http://retailcrm.ru
 * @see      http://retailcrm.ru/docs
 */

namespace Intaro\RetailCrm\Service;

use Bitrix\Sale\Internals\Fields;

/**
 * Class UploadOrderService
 *
 * @package Intaro\RetailCrm\Service
 */
class UploadOrderService
{
    /**
     * @param \Bitrix\Sale\Internals\Fields $product
     *
     * @return float
     */
    public function getDiscountManualAmount(Fields $product): float
    {
        if ($product->get('CUSTOM_PRICE') === 'Y') {
            return $product->get('BASE_PRICE') - $product->get('PRICE');
        }
        
        $discount = (double) $product->get('DISCOUNT_PRICE');
        $dpItem = $product->get('BASE_PRICE') - $product->get('PRICE');
    
        if ($dpItem > 0 && $discount <= 0) {
            return $dpItem;
        }
        
        return $discount;
    }
}
