<?php

echo htmlspecialchars_decode('&#8381;#');
    die();
    

use Bitrix\Currency\CurrencyLangTable;


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

$fieldMap = CurrencyLangTable::getMap();

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
    } elseif (strpos($fieldType['data_type'], '\\') !== false) {
        if (class_exists($fieldType['data_type'])) {
            $dataType = $fieldType['data_type'];
        } else {
            $dataType = 'mixed';
        }
    } else {
        $dataType = $fieldType['data_type'];
    }
    
    printf(' * @method %s %s()' . PHP_EOL, empty($dataType) ? 'mixed' : $dataType, $getter);
    printf(' * @method void %s(%s%s$%s)' . PHP_EOL, $setter, $dataType == 'mixed' ? '' : $dataType, $dataType == 'mixed' ? '' : ' ', $setArgumentName);
}