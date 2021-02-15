<?php

namespace Intaro\RetailCrm\Service;

use Bitrix\Main\Entity\DataManager;
use Bitrix\Main\Loader;
use Bitrix\Highloadblock as Highloadblock;


/**
 * Class Hl
 *
 * Позволяет получить DataManager для HL-блоков
 *
 * @package Intaro\RetailCrm\Service
 */
class Hl {
    /**
     * Получение DataManager класса управления HLBlock
     *
     * @param $HlBlockId
     * @return \Bitrix\Main\Entity\DataManager|null
     * @throws \Bitrix\Main\ArgumentException
     * @throws \Bitrix\Main\LoaderException
     * @throws \Bitrix\Main\ObjectPropertyException
     * @throws \Bitrix\Main\SystemException
     */
    public static function getHlClassById($HlBlockId): ?DataManager
    {
        Loader::includeModule('highloadblock');
        
        $hlblock = Highloadblock\HighloadBlockTable::getById($HlBlockId)->fetch();
        
        if (!$hlblock) {
            return null;
        }
        
        $entity = Highloadblock\HighloadBlockTable::compileEntity($hlblock);
        
        return $entity->getDataClass();
    }
    
    /**
     * Получение DataManager класса управления HLBlock
     *
     * @param $name
     * @return \Bitrix\Main\Entity\DataManager|string|null
     * @throws \Bitrix\Main\SystemException
     * @throws \Bitrix\Main\LoaderException
     */
    public static function getHlClassByName(string $name)
    {
        Loader::includeModule('highloadblock');
        
        $hlblock = Highloadblock\HighloadBlockTable::query()
            ->addSelect('*')
            ->addFilter('NAME', $name)
            ->exec()
            ->fetch();
        
        if (!$hlblock) {
            return null;
        }
        
        $entity = Highloadblock\HighloadBlockTable::compileEntity($hlblock);
        
        return $entity->getDataClass();
    }
}