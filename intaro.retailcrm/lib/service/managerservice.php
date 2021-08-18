<?php

namespace Intaro\RetailCrm\Service;

use Bitrix\Main\ArgumentException;
use Bitrix\Main\ObjectPropertyException;
use Bitrix\Main\SystemException;
use Intaro\RetailCrm\Repository\ManagerRepository;
use InvalidArgumentException;
use Logger;
use RetailCrm\ApiClient;
use RetailCrm\Component\Exception\FailedDbOperationException;
use RetailcrmConfigProvider;
use RetailcrmConstants;

/**
 * Отвечает за работу с ответственными лицами в заказах
 *
 * Class ManagerService
 *
 * @package Intaro\RetailCrm\Service
 */
class ManagerService
{
    protected static $instance;
    
    /**
     * @var \Intaro\RetailCrm\Repository\ManagerRepository
     */
    private $repository;

    /**
     * @var \RetailCrm\ApiClient
     */
    private $client;

    /**
     * @var \Logger
     */
    private $logger;

    /**
     * ManagerService constructor.
     */
    private function __construct()
    {
        $this->client = new ApiClient(RetailcrmConfigProvider::getApiUrl(), RetailcrmConfigProvider::getApiKey());
        $this->repository = new ManagerRepository();
        $this->logger = Logger::getInstance();
    }

    /**
     * @return \Intaro\RetailCrm\Service\ManagerService
     *
     * TODO заменить вызов на сервис-локатор, когда он приедет
     */
    public static function getInstance(): ManagerService
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        
        return self::$instance;
    }
    
    /**
     * Синхронизирует пользователей CRM и Битрикс
     */
    public function synchronizeManagers(): void
    {
        $currentPage = 1;
    
        RetailcrmConfigProvider::setUsersMap([]);

        do {
            $crmUsers = $this->getCrmUsersPage($currentPage);
            $matchesArray = $this->findMatchesInBitrix($crmUsers);

            if (!empty($matchesArray)) {
                try {
                    $this->repository->addManagersToMapping($matchesArray);
                } catch (FailedDbOperationException $exception) {
                    $this->logger->write(GetMessage('REP_ERR', ['#METHOD#' => __METHOD__]),'serviceErrors');
                }
            }

            $currentPage++;
        } while (count($crmUsers) > 0);
    }

    /**
     * @param string $bitrixUserId
     *
     * @return int|null
     */
    public function getManagerCrmId(string $bitrixUserId): ?int
    {
        $usersMap = RetailcrmConfigProvider::getUsersMap();

        return $usersMap[RetailcrmConstants::BITRIX_USER_ID_PREFIX . $bitrixUserId] ?? null;
    }

    /**
     * @param int|null $crmManagerId
     *
     * @return int
     */
    public function getManagerBitrixId(?int $crmManagerId): ?int
    {
        $usersMap = RetailcrmConfigProvider::getUsersMap();

        if (!is_array($usersMap) || count($usersMap) === 0) {
            return null;
        }

        $flipUserMap = array_flip($usersMap);

        if (!isset($flipUserMap[$crmManagerId])) {
            return null;
        }

        $managerId = str_replace(RetailcrmConstants::BITRIX_USER_ID_PREFIX, '', $flipUserMap[$crmManagerId]);

        if (empty($managerId)) {
            return null;
        }

        return (int) $managerId;
    }

    /**
     * @param int $pageNumber
     *
     * @return array
     */
    private function getCrmUsersPage(int $pageNumber): array
    {
        $response = $this->client->usersList([], $pageNumber);

        if (!$response->isSuccessful()) {
            return [];
        }

        try {
            $users = $response->offsetGet('users');

            if (is_array($users)) {
                return $users;
            }

            return [];
        } catch (InvalidArgumentException $exception) {
            return [];
        }
    }

    /**
     * @param array $crmUsers
     *
     * @return array
     */
    private function findMatchesInBitrix(array $crmUsers): array
    {
        $matchesUsers = [];

        foreach ($crmUsers as $crmUser) {
            $matchesUser = $this->getMatchesForCrmUser($crmUser);

            if (count($matchesUser) > 0) {
                $bitrixId = RetailcrmConstants::BITRIX_USER_ID_PREFIX . $matchesUser['bitrixUserId'];
                $matchesUsers[$bitrixId] = $matchesUser['crmUserId'];
            }
        }

        return $matchesUsers;
    }

    /**
     * @param array $crmUser
     *
     * @return array
     */
    private function getMatchesForCrmUser(array $crmUser): array
    {
        if (!empty($crmUser['email']) && !empty($crmUser['id'])) {
            try {
                $bitrixUserId = $this->repository->getManagerBitrixIdByEmail($crmUser['email']);

                if (is_int($bitrixUserId)) {
                    return [
                        'bitrixUserId' => $bitrixUserId,
                        'crmUserId' => $crmUser['id']
                    ];
                }
            } catch (ObjectPropertyException | ArgumentException | SystemException $e) {
                $this->logger->write(GetMessage('REP_ERR', ['#METHOD#' => __METHOD__]), 'serviceErrors');
            }
        }

        return [];
    }
}
