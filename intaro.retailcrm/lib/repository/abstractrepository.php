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

use Bitrix\Main\ORM\Entity;

/**
 * Class AbstractRepository
 *
 * @package Intaro\RetailCrm\Repository
 */
class AbstractRepository
{
    /**
     * Returns fields list for entity
     *
     * @param \Bitrix\Main\ORM\Entity $entity
     *
     * @return array
     */
    protected static function getEntityFields(Entity $entity): array
    {
        return array_keys($entity->getFields());
    }
}
