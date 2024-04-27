<?php

/**
 * @category Integration
 * @package  Intaro\RetailCrm\Repository
 * @author   RetailCRM <integration@retailcrm.ru>
 * @license  MIT
 * @link     http://retailcrm.ru
 * @see      http://retailcrm.ru/docs
 */

namespace Intaro\RetailCrm\Repository;

use Bitrix\Main\ORM\Objectify\EntityObject;
use Bitrix\Main\UserTable;
use RetailcrmConfigProvider;

/**
 * Class ManagerRepository
 *
 * @package Intaro\RetailCrm\Repository
 */
class ManagerRepository
{
    /**
     * @param array $newMatches
     *
     * @return void
     * @throws \Bitrix\Main\ArgumentOutOfRangeException
     */
    public function addManagersToMapping(array $newMatches): void
    {
        $usersMap = RetailcrmConfigProvider::getUsersMap();

        if (is_array($usersMap)) {
            $recordData = array_merge($usersMap, $newMatches);
        } else {
            $recordData = $newMatches;
        }

        RetailcrmConfigProvider::setUsersMap($recordData);
    }

    /**
     * @param string $email
     *
     * @return int|null
     * @throws \Bitrix\Main\ArgumentException
     * @throws \Bitrix\Main\ObjectPropertyException
     * @throws \Bitrix\Main\SystemException
     */
    public function getManagerBitrixIdByEmail(string $email): ?int
    {
            /** @var \Bitrix\Main\ORM\Objectify\EntityObject $user */
            $user = UserTable::query()
                ->addSelect('ID')
                ->where('EMAIL', $email)
                ->exec()
                ->fetchObject();

            if ($user instanceof EntityObject) {
                $userId = $user->get('ID');

                if (is_int($userId) && $userId > 0) {
                    return $userId;
                }
            }

            return null;
    }
}
