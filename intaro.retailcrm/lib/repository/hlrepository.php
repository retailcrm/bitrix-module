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

use Bitrix\Main\ArgumentException;
use Bitrix\Main\ObjectPropertyException;
use Bitrix\Main\SystemException;
use Intaro\RetailCrm\Service\Hl;
use Logger;

/**
 * Class HlRepository
 * @package Intaro\RetailCrm\Repository
 */
class HlRepository
{
    /**
     * @var \Bitrix\Main\Entity\DataManager|string|null
     */
    private $hl;
    
    public function __construct($hlName)
    {
        $this->hl = Hl::getHlClassByTableName($hlName);
    }
    
    /**
     * @param string|null $propertyValue
     * @return array|null
     */
    public function getDataByXmlId(?string $propertyValue): ?array
    {
        try {
            $result = $this->hl::query()
                ->setSelect(['*'])
                ->where('UF_XML_ID', '=', $propertyValue)
                ->fetch();
            
            if ($result === false) {
                return null;
            }
            
            return $result;
        } catch (ObjectPropertyException | ArgumentException | SystemException $exception) {
            Logger::getInstance()->write($exception->getMessage(), 'repositoryErrors');

            return null;
        }
    }
    
    /**
     * @return \Bitrix\Main\Entity\DataManager|string|null
     */
    public function getHl()
    {
        return $this->hl;
    }
}
