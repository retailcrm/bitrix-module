<?php

namespace Intaro\RetailCrm\Model\Bitrix\Orm;

use Bitrix\Main,
    Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

/**
 * Class ExportTable
 *
 * Fields:
 * <ul>
 * <li> ID int mandatory
 * <li> FILE_NAME string(100) mandatory
 * <li> NAME string(250) mandatory
 * <li> DEFAULT_PROFILE bool optional default 'N'
 * <li> IN_MENU bool optional default 'N'
 * <li> IN_AGENT bool optional default 'N'
 * <li> IN_CRON bool optional default 'N'
 * <li> SETUP_VARS string optional
 * <li> LAST_USE datetime optional
 * <li> IS_EXPORT bool optional default 'Y'
 * <li> NEED_EDIT bool optional default 'N'
 * <li> TIMESTAMP_X datetime optional
 * <li> MODIFIED_BY int optional
 * <li> DATE_CREATE datetime optional
 * <li> CREATED_BY int optional
 * </ul>
 *
 * @package Intaro\RetailCrm\Model\Bitrix\Orm
 **/
class ExportTable extends Main\Entity\DataManager
{
    /**
     * Returns DB table name for entity.
     *
     * @return string
     */
    public static function getTableName(): string
    {
        return 'b_catalog_export';
    }
    
    /**
     * Returns entity map definition.
     *
     * @return array
     */
    public static function getMap(): array
    {
        return [
            'ID'              => [
                'data_type'    => 'integer',
                'primary'      => true,
                'autocomplete' => true,
                'title'        => Loc::getMessage('EXPORT_ENTITY_ID_FIELD'),
            ],
            'FILE_NAME'       => [
                'data_type'  => 'string',
                'required'   => true,
                'validation' => [__CLASS__, 'validateFileName'],
                'title'      => Loc::getMessage('EXPORT_ENTITY_FILE_NAME_FIELD'),
            ],
            'NAME'            => [
                'data_type'  => 'string',
                'required'   => true,
                'validation' => [__CLASS__, 'validateName'],
                'title'      => Loc::getMessage('EXPORT_ENTITY_NAME_FIELD'),
            ],
            'DEFAULT_PROFILE' => [
                'data_type' => 'boolean',
                'values'    => ['N', 'Y'],
                'title'     => Loc::getMessage('EXPORT_ENTITY_DEFAULT_PROFILE_FIELD'),
            ],
            'IN_MENU'         => [
                'data_type' => 'boolean',
                'values'    => ['N', 'Y'],
                'title'     => Loc::getMessage('EXPORT_ENTITY_IN_MENU_FIELD'),
            ],
            'IN_AGENT'        => [
                'data_type' => 'boolean',
                'values'    => ['N', 'Y'],
                'title'     => Loc::getMessage('EXPORT_ENTITY_IN_AGENT_FIELD'),
            ],
            'IN_CRON'         => [
                'data_type' => 'boolean',
                'values'    => ['N', 'Y'],
                'title'     => Loc::getMessage('EXPORT_ENTITY_IN_CRON_FIELD'),
            ],
            'SETUP_VARS'      => [
                'data_type' => 'text',
                'title'     => Loc::getMessage('EXPORT_ENTITY_SETUP_VARS_FIELD'),
            ],
            'LAST_USE'        => [
                'data_type' => 'datetime',
                'title'     => Loc::getMessage('EXPORT_ENTITY_LAST_USE_FIELD'),
            ],
            'IS_EXPORT'       => [
                'data_type' => 'boolean',
                'values'    => ['N', 'Y'],
                'title'     => Loc::getMessage('EXPORT_ENTITY_IS_EXPORT_FIELD'),
            ],
            'NEED_EDIT'       => [
                'data_type' => 'boolean',
                'values'    => ['N', 'Y'],
                'title'     => Loc::getMessage('EXPORT_ENTITY_NEED_EDIT_FIELD'),
            ],
            'TIMESTAMP_X'     => [
                'data_type' => 'datetime',
                'title'     => Loc::getMessage('EXPORT_ENTITY_TIMESTAMP_X_FIELD'),
            ],
            'MODIFIED_BY'     => [
                'data_type' => 'integer',
                'title'     => Loc::getMessage('EXPORT_ENTITY_MODIFIED_BY_FIELD'),
            ],
            'DATE_CREATE'     => [
                'data_type' => 'datetime',
                'title'     => Loc::getMessage('EXPORT_ENTITY_DATE_CREATE_FIELD'),
            ],
            'CREATED_BY'      => [
                'data_type' => 'integer',
                'title'     => Loc::getMessage('EXPORT_ENTITY_CREATED_BY_FIELD'),
            ],
        ];
    }
    
    /**
     * Returns validators for FILE_NAME field.
     *
     * @return array
     * @throws \Bitrix\Main\ArgumentTypeException
     */
    public static function validateFileName()
    {
        return [
            new Main\Entity\Validator\Length(null, 100),
        ];
    }
    
    /**
     * Returns validators for NAME field.
     *
     * @return array
     * @throws \Bitrix\Main\ArgumentTypeException
     */
    public static function validateName()
    {
        return [
            new Main\Entity\Validator\Length(null, 250),
        ];
    }
}