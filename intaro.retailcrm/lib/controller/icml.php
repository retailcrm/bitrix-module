<?php

/**
 * PHP version 7.1
 *
 * @category Integration
 * @package  Intaro\RetailCrm\Controller\Loyalty
 * @author   RetailCRM <integration@retailcrm.ru>
 * @license  MIT
 * @link     http://retailcrm.ru
 * @see      http://retailcrm.ru/docs
 */

namespace Intaro\RetailCrm\Controller;

use Bitrix\Main\Engine\Controller;
use CModule;
use Intaro\RetailCrm\Service\Hl;

/**
 * Class Order
 *
 * @package Intaro\RetailCrm\Controller\Loyalty
 */
class Icml extends Controller
{
    /**
     * @param string|null $tableName
     *
     * @return array
     */
    public function getHlTableAction(?string $tableName): array
    {
        $hlBlockList = [];

        CModule::IncludeModule('highloadblock');
        $entity = Hl::getBaseEntityByTableName($tableName ?? null);

        if ($entity) {
            $hbFields = $entity->getFields();
            $hlBlockList['table'] = $entity->getDBTableName();

            foreach ($hbFields as $hbFieldCode => $hbField) {
                $hlBlockList['fields'][] = $hbFieldCode;
            }

            return $hlBlockList;
        }
    }
}
