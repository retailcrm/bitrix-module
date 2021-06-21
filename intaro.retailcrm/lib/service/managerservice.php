<?php

namespace Intaro\RetailCrm\Service;

use Intaro\RetailCrm\Repository\ManagerRepository;
use InvalidArgumentException;
use RetailCrm\ApiClient;
use RetailcrmConfigProvider;

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
     * ManagerService constructor.
     */
    private function __construct()
    {
        $this->client = new ApiClient(RetailcrmConfigProvider::getApiUrl(), RetailcrmConfigProvider::getApiKey());
        $this->repository = new ManagerRepository();
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
                $this->repository->addManagersToMapping($matchesArray);
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
        return $this->repository->getManagerCrmIdByBitrixId($bitrixUserId);
    }

    /**
     * @param int|null $crmManagerId
     *
     * @return int
     */
    public function getManagerBitrixId(?int $crmManagerId): ?int
    {
        return $this->repository->getBitrixIdByCrmId($crmManagerId);
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
                $bitrixId = 'bitrixUserId-' . $matchesUser['bitrixUserId'];
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
            $bitrixUserId = $this->repository->getManagerBitrixIdByEmail($crmUser['email']);

            if (is_int($bitrixUserId)) {
                return [
                    'bitrixUserId' => $bitrixUserId,
                    'crmUserId' => $crmUser['id']
                ];
            }
        }

        return [];
    }
}
