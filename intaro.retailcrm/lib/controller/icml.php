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

use Bitrix\Main\Engine\ActionFilter\Authentication;
use Bitrix\Main\Engine\ActionFilter\Csrf;
use Bitrix\Main\Engine\Controller;
use Bitrix\Main\Error;
use CModule;
use Intaro\RetailCrm\Component\Constants;
use Intaro\RetailCrm\Service\Hl;

/**
 * @category Integration
 * @package  Intaro\RetailCrm\Controller
 * @author   RetailCRM <integration@retailcrm.ru>
 * @license  MIT
 * @link     http://retailcrm.ru
 * @see      http://retailcrm.ru/docs
 */
class Icml extends Controller
{
    public function configureActions(): array
    {
        return [
            'getHlTable' => [
                'prefilters' => [
                    new Authentication(),
                    new Csrf(),
                ],
            ],
        ];
    }

    /**
     * @param string|null $tableName
     *
     * @return array
     */
    public function getHlTableAction(?string $tableName): array
    {
        if (!$this->hasWriteAccess()) {
            $this->addError(new Error('Access denied'));

            return [];
        }

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

        return [];
    }

    private function hasWriteAccess(): bool
    {
        global $APPLICATION, $USER;

        return $USER instanceof \CUser
            && (
                $USER->IsAdmin()
                || ($APPLICATION instanceof \CMain && $APPLICATION->GetGroupRight(Constants::MODULE_ID) === 'W')
            );
    }
}
