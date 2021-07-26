<?php


namespace Intaro\RetailCrm\Repository;

use Bitrix\Main\ORM\Objectify\EntityObject;
use Bitrix\Main\UserTable;
use RetailCrm\Component\Exception\FailedDbOperationException;
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
     * @throws \RetailCrm\Component\Exception\FailedDbOperationException
     */
    public function addManagersToMapping(array $newMatches): void
    {
        $usersMap = RetailcrmConfigProvider::getUsersMap();

        if (is_array($usersMap)) {
            $recordData = array_merge($usersMap, $newMatches);
        } else {
            $recordData = $newMatches;
        }

        if (!RetailcrmConfigProvider::setUsersMap($recordData)) {
            throw new FailedDbOperationException();
        }
    }

    /**
     * @param string $bitrixId
     *
     * @return int|null
     */
    public function getManagerCrmIdByBitrixId(string $bitrixId): ?int
    {
        $usersMap = RetailcrmConfigProvider::getUsersMap();

        return $usersMap['bitrixUserId-' . $bitrixId] ?? null;
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

    /**
     * @param int|null $crmManagerId
     *
     * @return int|null
     */
    public function getBitrixIdByCrmId(?int $crmManagerId): ?int
    {
        $usersMap = RetailcrmConfigProvider::getUsersMap();

        if (!is_array($usersMap) || count($usersMap) === 0) {
            return null;
        }

        $flipUserMap = array_flip($usersMap);

        if (!isset($flipUserMap[$crmManagerId])) {
            return null;
        }

        $managerId = str_replace('bitrixUserId-', '', $flipUserMap[$crmManagerId]);

        if (empty($managerId)) {
            return null;
        }

        return (int) $managerId;
    }
}
