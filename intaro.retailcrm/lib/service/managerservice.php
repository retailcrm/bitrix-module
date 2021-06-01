<?php

namespace Intaro\RetailCrm\Service;

use Intaro\RetailCrm\Repository\ManagerRepository;
use InvalidArgumentException;
use Logger;
use RCrmActions;
use RetailCrm\ApiClient;
use RetailCrm\Response\ApiResponse;
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
    public function __construct()
    {
        $this->client = new ApiClient(RetailcrmConfigProvider::getApiUrl(), RetailcrmConfigProvider::getApiKey());
        $this->repository = new ManagerRepository();
    }
    
    /**
     * Синхронизирует пользователей CRM и Битрикс
     */
    public function synchronizeManagers(): void
    {
        $currentPage = 1;
    
        do {
            $crmUsers = $this->getCrmUsersPage($currentPage);
            $matchesArray = $this->findMatchesInBitrix($crmUsers);
            
            $this->repository->addManagersToMapping($matchesArray);
        
            $currentPage++;
        } while (count($crmUsers) > 0);
    }
    
    /**
     * @param int $bitrixUserId
     *
     * @return int|null
     */
    public function getManagerCrmId(int $bitrixUserId): ?int
    {
        return $this->repository->getManagerCrmIdByBitrixId($bitrixUserId);
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

            if (count($matchesUser)>0) {
                $matchesUsers[] = $matchesUser;
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
                return [$bitrixUserId => $crmUser['id']];
            }
        }
    
        return [];
    }
}