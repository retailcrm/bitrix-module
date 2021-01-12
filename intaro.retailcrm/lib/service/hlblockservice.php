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

use Intaro\RetailCrm\Component\Constants;

/**
 * Class HlBlockService
 * @package Intaro\RetailCrm\Service
 */
class HlBlockService {
    
    /**
     * Записывает информацию о скидках по программе лояльности в HL блок
     *
     * @param int    $orderId
     * @param int    $bonusCount
     * @param int    $rate
     * @param bool   $isDebited
     * @param string $checkId
     * @throws \Bitrix\Main\LoaderException
     * @throws \Bitrix\Main\SystemException
     * @throws \Exception
     */
    public static function addDataInLoyaltyHl(int $orderId, int $bonusCount, int $rate, bool $isDebited = false, string $checkId = ''): void
    {
        $dataManager = Utils::getHlClassByName(Constants::HL_LOYALTY_CODE);
        
        if ($dataManager === null) {
            return;
        }
        
        $data = [
            "UF_ORDER_ID"       => $orderId,
            "UF_CASH_DISCOUNT"  => $rate * $bonusCount,
            "UF_BONUS_RATE"     => $rate,
            "UF_BONUS_COUNT"    => $bonusCount,
            "UF_PRIVILEGE_TYPE" => 'тип привилегии',//TODO настроить запись
            "UF_IS_DEBITED"     => $isDebited,
            "UF_CHECK_ID"       => $checkId,
        ];
        
        $dataManager::add($data);
    }
}