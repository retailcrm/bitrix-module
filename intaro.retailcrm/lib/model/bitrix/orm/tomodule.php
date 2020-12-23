<?php

namespace Intaro\RetailCrm\Model\Bitrix\ORM;

use Bitrix\Main,
    Bitrix\Main\Localization\Loc;
use Bitrix\Main\ORM\Fields\IntegerField;
use Bitrix\Main\ORM\Fields\DatetimeField;
use Bitrix\Main\ORM\Fields\StringField;

Loc::loadMessages(__FILE__);

/**
 * Class ToModuleTable
 *
 * Fields:
 * <ul>
 * <li> ID int mandatory
 * <li> TIMESTAMP_X datetime mandatory default 'CURRENT_TIMESTAMP'
 * <li> SORT int optional default 100
 * <li> FROM_MODULE_ID string(50) mandatory
 * <li> MESSAGE_ID string(255) mandatory
 * <li> TO_MODULE_ID string(50) mandatory
 * <li> TO_PATH string(255) optional
 * <li> TO_CLASS string(255) optional
 * <li> TO_METHOD string(255) optional
 * <li> TO_METHOD_ARG string(255) optional
 * <li> VERSION int optional
 * <li> UNIQUE_ID string(32) mandatory
 * </ul>
 *
 * @package Bitrix\Module
 **/
class ToModuleTable extends Main\Entity\DataManager
{
    /**
     * Returns DB table name for entity.
     *
     * @return string
     */
    public static function getTableName()
    {
        return 'b_module_to_module';
    }
    
    /**
     * Returns entity map definition.
     *
     * @return array
     * @throws \Bitrix\Main\SystemException
     */
    public static function getMap()
    {
        return [
            new IntegerField(
                'id',
                ['primary' => true, 'autocomplete' => true]),
            new DatetimeField('TIMESTAMP_X', ['required' => true]),
            new IntegerField('sort'),
            new StringField(
                'from_module_id',
                ['required' => true, 'validation' => [__CLASS__, 'validateFromModuleId']]
            ),
            new StringField(
                'message_id',
                ['required' => true, 'validation' => [__CLASS__, 'validateMessageId']]
            ),
            new StringField(
                'to_module_id',
                ['required' => true, 'validation' => [__CLASS__, 'validateToModuleId']]
            ),
            new StringField(
                'to_path',
                ['validation' => [__CLASS__, 'validateToPath']]
            ),
            new StringField(
                'to_class',
                ['validation' => [__CLASS__, 'validateToClass']]
            ),
            new StringField(
                'to_method',
                ['validation' => [__CLASS__, 'validateToMethod']]
            ),
            new StringField(
                'to_method_arg',
                ['validation' => [__CLASS__, 'validateToMethodArg']]
            ),
            new IntegerField('version'),
            new StringField(
                'unique_id',
                ['required' => true, 'validation' => [__CLASS__, 'validateUniqueId']]
            ),
        ];
    }
    
    /**
     * Returns validators for FROM_MODULE_ID field.
     *
     * @return array
     * @throws \Bitrix\Main\ArgumentTypeException
     */
    public static function validateFromModuleId()
    {
        return [
            new Main\Entity\Validator\Length(null, 50),
        ];
    }
    
    /**
     * Returns validators for MESSAGE_ID field.
     *
     * @return array
     * @throws \Bitrix\Main\ArgumentTypeException
     */
    public static function validateMessageId()
    {
        return [
            new Main\Entity\Validator\Length(null, 255),
        ];
    }
    
    /**
     * Returns validators for TO_MODULE_ID field.
     *
     * @return array
     * @throws \Bitrix\Main\ArgumentTypeException
     */
    public static function validateToModuleId()
    {
        return [
            new Main\Entity\Validator\Length(null, 50),
        ];
    }
    
    /**
     * Returns validators for TO_PATH field.
     *
     * @return array
     * @throws \Bitrix\Main\ArgumentTypeException
     */
    public static function validateToPath()
    {
        return [
            new Main\Entity\Validator\Length(null, 255),
        ];
    }
    
    /**
     * Returns validators for TO_CLASS field.
     *
     * @return array
     * @throws \Bitrix\Main\ArgumentTypeException
     */
    public static function validateToClass()
    {
        return [
            new Main\Entity\Validator\Length(null, 255),
        ];
    }
    
    /**
     * Returns validators for TO_METHOD field.
     *
     * @return array
     * @throws \Bitrix\Main\ArgumentTypeException
     */
    public static function validateToMethod()
    {
        return [
            new Main\Entity\Validator\Length(null, 255),
        ];
    }
    
    /**
     * Returns validators for TO_METHOD_ARG field.
     *
     * @return array
     * @throws \Bitrix\Main\ArgumentTypeException
     */
    public static function validateToMethodArg()
    {
        return [
            new Main\Entity\Validator\Length(null, 255),
        ];
    }
    
    /**
     * Returns validators for UNIQUE_ID field.
     *
     * @return array
     * @throws \Bitrix\Main\ArgumentTypeException
     */
    public static function validateUniqueId()
    {
        return [
            new Main\Entity\Validator\Length(null, 32),
        ];
    }
}