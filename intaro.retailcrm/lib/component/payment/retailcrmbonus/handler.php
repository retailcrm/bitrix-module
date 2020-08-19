<?php

namespace Intaro\RetailCrm\Component\Payment\RetailCrmBonus;

use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Request;
use Bitrix\Sale\PaySystem;
use Bitrix\Sale\Payment;

Loc::loadMessages(__FILE__);

/**
 * Class RetailCrmBonusHandler
 * @package Sale\Handlers\PaySystem
 */
class RetailCrmBonusHandler extends PaySystem\BaseServiceHandler
{
    /**
     * @param Payment      $payment
     * @param Request|null $request
     * @return PaySystem\ServiceResult
     */
    public function initiatePay(Payment $payment, Request $request = null)
    {
        return new PaySystem\ServiceResult();
    }
    
    /**
     * @return array
     */
    public function getCurrencyList()
    {
        return [];
    }
    
}