<?php

/**
 * PHP version 7.1
 *
 * @category Integration
 * @package  Intaro\RetailCrm\Model\Api
 * @author   RetailCRM <integration@retailcrm.ru>
 * @license  MIT
 * @link     http://retailcrm.ru
 * @see      http://retailcrm.ru/docs
 */

namespace Intaro\RetailCrm\Repository;

use Bitrix\Currency\CurrencyLangTable;
use Bitrix\Main\ArgumentException;
use Bitrix\Main\ObjectPropertyException;
use Bitrix\Main\SystemException;
use Intaro\RetailCrm\Component\ConfigProvider;

/**
 * Class CurrencyRepository
 */
class CurrencyRepository extends AbstractRepository
{
    /**
     * @return string|null
     */
    public function getCurrencyFormatString(): ?string
    {
        try {
            $currency = CurrencyLangTable::query()
                ->setSelect(['FORMAT_STRING'])
                ->where([
                    ['CURRENCY', '=', ConfigProvider::getCurrencyOrDefault()],
                    ['LID', '=', 'LANGUAGE_ID'],
                ])
                ->fetch();
            
            if ($currency === false || !isset($currency['FORMAT_STRING'])) {
                return null;
            }
            
            return $currency['FORMAT_STRING'];
        } catch (ObjectPropertyException | ArgumentException | SystemException $exception) {
            AddMessage2Log($exception->getMessage());
        }
    }
}
