<?php
/**
 * PHP version 7.1
 *
 * @category Integration
 * @package  Intaro\RetailCrm\Model\Api
 * @author   retailCRM <integration@retailcrm.ru>
 * @license  MIT
 * @link     http://retailcrm.ru
 * @see      http://retailcrm.ru/docs
 */
namespace Intaro\RetailCrm\Repository;

use Intaro\RetailCrm\Component\Json\Deserializer;
use Intaro\RetailCrm\Model\Bitrix\User;

/**
 * Class UserRepository
 *
 * @package Intaro\RetailCrm\Repository
 */
class UserRepository extends AbstractRepository
{
    /**
     * @param int $id
     *
     * @return User|null
     */
    public static function getById(int $id): ?User
    {
        $dbResult = \CUser::GetByID($id);
        $fields = $dbResult->Fetch();

        if (!$fields) {
            return null;
        }

        return Deserializer::deserializeArray($fields, User::class);
    }
}
