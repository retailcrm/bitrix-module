<?php

namespace Intaro\RetailCrm\Service;

use Bitrix\Main\ArgumentException;
use Bitrix\Main\Entity\DataManager;
use Bitrix\Main\Loader;
use Bitrix\Highloadblock as Highloadblock;
use Bitrix\Main\LoaderException;
use Bitrix\Main\ObjectPropertyException;
use Bitrix\Main\SystemException;

/**
 * Class Hl
 *
 * Позволяет получить DataManager для HL-блоков
 *
 * @package Intaro\RetailCrm\Service
 */
class Hl
{
    /**
     * Получение DataManager класса управления HLBlock
     *
     * @param int $HlBlockId
     * @return \Bitrix\Main\Entity\DataManager|null
     * @throws \Bitrix\Main\ArgumentException
     * @throws \Bitrix\Main\LoaderException
     * @throws \Bitrix\Main\ObjectPropertyException
     * @throws \Bitrix\Main\SystemException
     */
    public static function getHlClassById(int $HlBlockId): ?DataManager
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
     * Получение DataManager класса управления HLBlock по названию таблицы
     *
     * @param string $name
     * @return \Bitrix\Main\Entity\DataManager|string|null
     */
    public static function getHlClassByTableName(string $name)
    {
        try {
            Loader::includeModule('highloadblock');
            
            $hlblock = Highloadblock\HighloadBlockTable::query()
                ->addSelect('*')
                ->where('TABLE_NAME', '=', $name)
                ->exec()
                ->fetch();
            
            if (!$hlblock) {
                return null;
            }
            
            $entity = Highloadblock\HighloadBlockTable::compileEntity($hlblock);
            
            return $entity->getDataClass();
        } catch (ObjectPropertyException | ArgumentException | SystemException | LoaderException $exception) {
            AddMessage2Log($exception->getMessage());
            return null;
        }
    }
}
