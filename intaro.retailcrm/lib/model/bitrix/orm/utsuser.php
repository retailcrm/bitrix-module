<?php

namespace Intaro\RetailCrm\Model\Bitrix\ORM;

use Bitrix\Main,
    Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

/**
 * Class UtsUserTable
 *
 * Fields:
 * <ul>
 * <li> VALUE_ID int mandatory
 * <li> UF_CARD_NUM_INTARO string optional
 * <li> UF_LP_ID_INTARO string optional
 * <li> UF_REG_IN_PL_INTARO int optional
 * <li> UF_AGREE_PL_INTARO int optional
 * <li> UF_PD_PROC_PL_INTARO int optional
 * <li> UF_EXT_REG_PL_INTARO int optional
 * </ul>
 *
 * @package Bitrix\Uts
 **/
class UtsUserTable extends Main\Entity\DataManager
{
    /**
     * Returns DB table name for entity.
     *
     * @return string
     */
    public static function getTableName()
    {
        return 'b_uts_user';
    }
    
    /**
     * Returns entity map definition.
     *
     * @return array
     */
    public static function getMap()
    {
        return [
            'VALUE_ID'             => [
                'data_type' => 'integer',
                'primary'   => true,
                'title'     => Loc::getMessage('USER_ENTITY_VALUE_ID_FIELD'),
            ],
            'UF_CARD_NUM_INTARO'   => [
                'data_type' => 'text',
                'title'     => Loc::getMessage('USER_ENTITY_UF_CARD_NUM_INTARO_FIELD'),
            ],
            'UF_LP_ID_INTARO'      => [
                'data_type' => 'text',
                'title'     => Loc::getMessage('USER_ENTITY_UF_LP_ID_INTARO_FIELD'),
            ],
            'UF_REG_IN_PL_INTARO'  => [
                'data_type' => 'integer',
                'title'     => Loc::getMessage('USER_ENTITY_UF_REG_IN_PL_INTARO_FIELD'),
            ],
            'UF_AGREE_PL_INTARO'   => [
                'data_type' => 'integer',
                'title'     => Loc::getMessage('USER_ENTITY_UF_AGREE_PL_INTARO_FIELD'),
            ],
            'UF_PD_PROC_PL_INTARO' => [
                'data_type' => 'integer',
                'title'     => Loc::getMessage('USER_ENTITY_UF_PD_PROC_PL_INTARO_FIELD'),
            ],
            'UF_EXT_REG_PL_INTARO' => [
                'data_type' => 'integer',
                'title'     => Loc::getMessage('USER_ENTITY_UF_EXT_REG_PL_INTARO_FIELD'),
            ],
        ];
    }
}
