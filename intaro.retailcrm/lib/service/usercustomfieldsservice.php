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
use RCrmActions;

/**
 * Builds retailCRM custom fields from mapped Bitrix user fields.
 */
class UserCustomFieldsService
{
    public const STRING_MAX_LENGTH = 500;

    public const DIRECTION_TO_CRM = 'Bitrix -> RetailCRM';
    public const DIRECTION_TO_CMS = 'RetailCRM -> Bitrix';

    private const STRING_TYPES = ['STRING', 'string', 'text'];

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

            $type = $fieldTypes[$bitrixCode] ?? '';

            if ($this->isStringLengthExceeded($userFields[$bitrixCode], $type)) {
                $this->logStringLengthExceeded(
                    __METHOD__,
                    self::DIRECTION_TO_CRM,
                    'customer' . (isset($userFields['ID']) ? ' ' . $userFields['ID'] : ''),
                    (string) $bitrixCode,
                    (string) $crmCode,
                    $userFields[$bitrixCode]
                );

                continue;
            }

            $result[$crmCode] = $this->convertValue($userFields[$bitrixCode], $type);
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
     * @param mixed $value
     * @param mixed $type
     *
     * @return bool
     */
    public function isStringLengthExceeded($value, $type): bool
    {
        return in_array($type, self::STRING_TYPES, true)
            && is_scalar($value)
            && mb_strlen((string) $value) > self::STRING_MAX_LENGTH;
    }

    /**
     * @param string $method
     * @param string $direction
     * @param string $entity
     * @param string $bitrixCode
     * @param string $crmCode
     * @param mixed  $value
     */
    public function logStringLengthExceeded(
        string $method,
        string $direction,
        string $entity,
        string $bitrixCode,
        string $crmCode,
        $value
    ): void {
        RCrmActions::eventLog(
            $method,
            'CustomFieldLengthExceeded',
            sprintf(
                'Custom field value is too long and was not transferred. Direction: %s; entity: %s;'
                . ' Bitrix field: %s; RetailCRM field: %s; length: %d; limit: %d',
                $direction,
                $entity,
                $bitrixCode,
                $crmCode,
                mb_strlen((string) $value),
                self::STRING_MAX_LENGTH
            )
        );
    }

    /**
     * @param mixed  $value
     * @param mixed  $type
     *
     * @return mixed null if the string value exceeds the limit, see isStringLengthExceeded()
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
                return $this->isStringLengthExceeded($value, $type) ? null : $value;
            case 'datetime':
                return date('Y-m-d', strtotime($value));
            default:
                return $value;
        }
    }
}
