<?php

/**
 * @category Integration
 * @package  Intaro\RetailCrm\Service
 * @author   RetailCRM <integration@retailcrm.ru>
 * @license  MIT
 * @link     http://retailcrm.ru
 * @see      http://retailcrm.ru/docs
 */

namespace Intaro\RetailCrm\Service;

use Bitrix\Main\UserFieldTable;
use Intaro\RetailCrm\Component\ConfigProvider;

/**
 * Builds retailCRM custom fields from mapped Bitrix user fields.
 */
class UserCustomFieldsService
{
    /**
     * @param array $userFields
     *
     * @return array
     */
    public function getCustomFields(array $userFields): array
    {
        $matchedFields = (array) ConfigProvider::getMatchedUserFields();
        $fieldTypes = $this->getUserFieldTypes(array_keys($matchedFields));
        $result = [];

        foreach ($matchedFields as $bitrixCode => $crmCode) {
            if (!isset($userFields[$bitrixCode])) {
                continue;
            }

            $result[$crmCode] = $this->convertValue(
                $userFields[$bitrixCode],
                $fieldTypes[$bitrixCode] ?? ''
            );
        }

        return $result;
    }

    /**
     * @param array $fieldNames
     *
     * @return array
     */
    public function getUserFieldTypes(array $fieldNames = []): array
    {
        $filter = [
            ['ENTITY_ID' => 'USER'],
            ['!=FIELD_NAME' => 'UF_SUBSCRIBE_USER_EMAIL'],
            ['?USER_TYPE_ID' => 'string | date | datetime | integer | double | boolean'],
            ['MULTIPLE' => 'N'],
        ];

        if (empty($fieldNames)) {
            $filter[] = ['?FIELD_NAME' => '~%INTARO%'];
        } else {
            $filter[] = ['@FIELD_NAME' => $fieldNames];
        }

        $userFields = UserFieldTable::getList([
            'select' => ['FIELD_NAME', 'USER_TYPE_ID'],
            'filter' => $filter,
        ])->fetchAll();

        $result = [];

        foreach ($userFields as $userField) {
            $result[$userField['FIELD_NAME']] = $userField['USER_TYPE_ID'];
        }

        return $result;
    }

    /**
     * @param mixed  $value
     * @param mixed  $type
     *
     * @return mixed
     */
    public function convertValue($value, $type)
    {
        switch ($type) {
            case 'boolean':
                return $value === '1' ? 1 : 0;
            case 'Y/N':
                return $value === 'Y' ? 1 : 0;
            case 'STRING':
            case 'string':
                return strlen($value) <= 500 ? $value : '';
            case 'datetime':
                return date('Y-m-d', strtotime($value));
            default:
                return $value;
        }
    }
}
