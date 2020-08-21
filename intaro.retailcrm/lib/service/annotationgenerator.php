<?php

function bitrixNameToCamelCase($string, $capitalizeFirstCharacter = false)
{
    $str = str_replace(' ', '', ucwords(str_replace('_', ' ', strtolower($string))));
    
    if (!$capitalizeFirstCharacter) {
        $str[0] = strtolower($str[0]);
    }
    
    return $str;
}

$typeMapping = [
    'integer' => 'int',
    'string' => 'string',
    'text' => 'string',
    'boolean' => 'bool',
    'datetime' => 'DateTime',
    'date' => 'DateTime'
];

$fieldMap = array(
    'ID' => array(
        'primary' => true,
        'autocomplete' => true,
        'data_type' => 'integer',
        'format' => '/^[0-9]{1,11}$/',
    ),
    'PERSON_TYPE_ID' => array(
        'required' => true,
        'data_type' => 'integer',
        'format' => '/^[0-9]{1,11}$/',
    ),
    'NAME' => array(
        'required' => true,
        'data_type' => 'string',
        
        
    ),
    'TYPE' => array(
        'required' => true,
        'data_type' => 'string',
        
    ),
    'REQUIRED' => array(
        'data_type' => 'boolean',
        'values' => array('N', 'Y'),
        
    ),
    'DEFAULT_VALUE' => array(
        'data_type' => 'string',
        
        
        
        
    ),
    'SORT' => array(
        'data_type' => 'integer',
        'format' => '/^[0-9]{1,11}$/',
        
    ),
    'USER_PROPS' => array(
        'data_type' => 'boolean',
        'values' => array('N', 'Y'),
    ),
    'IS_LOCATION' => array(
        'data_type' => 'boolean',
        'values' => array('N', 'Y'),
    ),
    'PROPS_GROUP_ID' => array(
        'required' => true,
        'data_type' => 'integer',
        'format' => '/^[0-9]{1,11}$/',
    ),
    'DESCRIPTION' => array(
        'data_type' => 'string',
        
        
    ),
    'IS_EMAIL' => array(
        'data_type' => 'boolean',
        'values' => array('N', 'Y'),
    ),
    'IS_PROFILE_NAME' => array(
        'data_type' => 'boolean',
        'values' => array('N', 'Y'),
    ),
    'IS_PAYER' => array(
        'data_type' => 'boolean',
        'values' => array('N', 'Y'),
    ),
    'IS_LOCATION4TAX' => array(
        'data_type' => 'boolean',
        'values' => array('N', 'Y'),
    ),
    'IS_FILTERED' => array(
        'data_type' => 'boolean',
        'values' => array('N', 'Y'),
        
    ),
    'CODE' => array(
        'data_type' => 'string',
        
        
    ),
    'IS_ZIP' => array(
        'data_type' => 'boolean',
        'values' => array('N', 'Y'),
    ),
    'IS_PHONE' => array(
        'data_type' => 'boolean',
        'values' => array('N', 'Y'),
    ),
    'IS_ADDRESS' => array(
        'data_type' => 'boolean',
        'values' => array('N', 'Y'),
    ),
    'ACTIVE' => array(
        'data_type' => 'boolean',
        'values' => array('N', 'Y'),
    ),
    'UTIL' => array(
        'data_type' => 'boolean',
        'values' => array('N', 'Y'),
    ),
    'INPUT_FIELD_LOCATION' => array(
        'data_type' => 'integer',
        'format' => '/^[0-9]{1,11}$/',
    ),
    'MULTIPLE' => array(
        'data_type' => 'boolean',
        'values' => array('N', 'Y'),
    ),
    'SETTINGS' => array(
        'data_type' => 'string',
        
        
        
    ),
    
    'GROUP' => array(
        'data_type' => 'Bitrix\Sale\Internals\OrderPropsGroupTable',
        'reference' => array('=this.PROPS_GROUP_ID' => 'ref.ID'),
        'join_type' => 'LEFT',
    ),
    'PERSON_TYPE' => array(
        'data_type' => 'Bitrix\Sale\Internals\PersonTypeTable',
        'reference' => array('=this.PERSON_TYPE_ID' => 'ref.ID'),
        'join_type' => 'LEFT',
    ),
    'ENTITY_REGISTRY_TYPE' => array(
        'data_type' => 'string',
    ),
    'XML_ID' => array(
        'data_type' => 'string',
    ),
); //replace this array AnyTable::getMap();

foreach ($fieldMap as $fieldName => $fieldType) {
    if (!is_string($fieldName)) {
        continue;
    }
    
    $setArgumentName = bitrixNameToCamelCase($fieldName);
    $getter = bitrixNameToCamelCase('GET_' . $fieldName);
    $setter = bitrixNameToCamelCase('SET_' . $fieldName);
    $dataType = '';
    
    if (isset($typeMapping[$fieldType['data_type']])) {
        $dataType = $typeMapping[$fieldType['data_type']];
    } else {
        if (strpos($fieldType['data_type'], '\\') !== false) {
            if (class_exists($fieldType['data_type'])) {
                $dataType = $fieldType['data_type'];
            } else {
                $dataType = 'mixed';
            }
        } else {
            $dataType = $fieldType['data_type'];
        }
    }
    
    printf(' * @method %s %s()' . PHP_EOL, empty($dataType) ? 'mixed' : $dataType, $getter);
    printf(' * @method void %s(%s%s$%s)' . PHP_EOL, $setter, $dataType == 'mixed' ? '' : $dataType, $dataType == 'mixed' ? '' : ' ', $setArgumentName);
}