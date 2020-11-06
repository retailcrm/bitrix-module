<?php
/**
 * PHP version 7.1
 *
 * @category Integration
 * @package  Intaro\RetailCrm\Controller\Loyalty
 * @author   retailCRM <integration@retailcrm.ru>
 * @license  MIT
 * @link     http://retailcrm.ru
 * @see      http://retailcrm.ru/docs
 */

namespace Intaro\RetailCrm\Controller\Loyalty;

use Bitrix\Main\Engine\ActionFilter\Authentication;
use Bitrix\Main\Engine\ActionFilter\HttpMethod;
use Bitrix\Main\Engine\Controller;
use Bitrix\Main\Request;
use Exception;
use Intaro\RetailCrm\Component\ServiceLocator;
use Intaro\RetailCrm\Service\LoyaltyService;

/**
 * Class AdminPanel
 * @package Intaro\RetailCrm\Controller\Loyalty
 */
class Basket extends Controller
{
    /** @var LoyaltyService */
    private $service;
    
    /**
     * AdminPanel constructor.
     *
     * @param \Bitrix\Main\Request|null $request
     */
    public function __construct(Request $request = null)
    {
        $this->service = ServiceLocator::get(LoyaltyService::class);
        parent::__construct($request);
    }
    
    /**
     * @param array $basketData
     * @return array
     */
    public function calculateBasketBonusesAction(array $basketData): array
    {
        $discountPercent = round($basketData['DISCOUNT_PRICE_ALL'] / ($basketData['allSum'] / 100), 0);
        $calculate       = $this->service->calculateBonus($basketData['BASKET_ITEM_RENDER_DATA'], $basketData['DISCOUNT_PRICE_ALL'], $discountPercent);
        
        if ($calculate->success) {
            $response['LP_CALCULATE_SUCCESS'] = $calculate->success;
            $response['WILL_BE_CREDITED']     = $calculate->order->bonusesCreditTotal;
        }
        
        foreach ($basketData['BASKET_ITEM_RENDER_DATA'] as $key => &$item) {
            $item['WILL_BE_CREDITED_BONUS'] = $calculate->order->items[$key]->bonusesCreditTotal;
        }
        
        $response['BASKET_ITEM_RENDER_DATA'] = $basketData['BASKET_ITEM_RENDER_DATA'];
        
        return $response;
    }
    
    /**
     * @return \array[][]
     */
    public function sendVerificationCode(): array
    {
        return [
            'calculateBasketBonuses' => [
                '-prefilters' => [
                    new Authentication,
                    new HttpMethod(['POST']),
                ],
            ],
        ];
    }
}
