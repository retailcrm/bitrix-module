<?php


namespace Intaro\RetailCrm\Repository;

use Bitrix\Main\ArgumentException;
use Bitrix\Main\ObjectPropertyException;
use Bitrix\Main\SystemException;
use Bitrix\Main\UserTable;
use Logger;
use RetailcrmConfigProvider;

/**
 * Class ManagerRepository
 *
 * @package Intaro\RetailCrm\Repository
 */
class ManagerRepository
{
    /**
     * @var \Logger
     */
    private $logger;
    
    public function __construct()
    {
        $this->logger = Logger::getInstance();
    }
    
    /**
     * @param array $newMatches
     *
     * @return void
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
            $this->logger->write(GetMessage('REP_ERR') . __METHOD__, 'repositoryErrors');
        }
    }
    
    /**
     * @param int $bitrixId
     *
     * @return int|null
     */
    public function getManagerCrmIdByBitrixId(int $bitrixId): ?int
    {
        $usersMap = RetailcrmConfigProvider::getUsersMap();
        
        return $usersMap[$bitrixId] ?? null;
    }
    
    /**
     * @param string $email
     *
     * @return int|null
     */
    public function getManagerBitrixIdByEmail(string $email): ?int
    {
        try {
            /** @var \Bitrix\Main\ORM\Objectify\EntityObject $user */
            $user = UserTable::query()
                ->addSelect('ID')
                ->where('EMAIL', $email)
                ->fetchObject();
            $userId = $user->get('ID');
            
            if (is_int($userId) && $userId > 0) {
                return $user->get('ID');
            }
            
            return null;
        }catch (ObjectPropertyException | ArgumentException | SystemException $exception) {
            $this->logger->write(GetMessage('REP_ERR') . __METHOD__, 'repositoryErrors');
        }
    }
}
