<?php

use Intaro\RetailCrm\Component\ConfigProvider;
use Intaro\RetailCrm\Service\UserCustomFieldsService;
use Tests\Intaro\RetailCrm\Helpers;

/**
 * Class UserCustomFieldsServiceTest
 */
class UserCustomFieldsServiceTest extends BitrixTestCase
{
    private const BITRIX_FIELD = 'UF_INTARO_NOTE';
    private const CRM_FIELD = 'note';

    public function setUp(): void
    {
        parent::setUp();

        COption::SetOptionString('intaro.retailcrm', 'api_version', 'v5');
        CModule::IncludeModule('intaro.retailcrm');
    }

    /**
     * @dataProvider stringLengthProvider
     *
     * @param mixed $value
     */
    public function testIsStringLengthExceeded($value, string $type, bool $expected): void
    {
        self::assertSame($expected, (new UserCustomFieldsService())->isStringLengthExceeded($value, $type));
    }

    public function stringLengthProvider(): array
    {
        return [
            'cyrillic string shorter than the limit' => [str_repeat('я', 454), 'STRING', false],
            'exactly 500 characters' => [str_repeat('я', 500), 'string', false],
            '501 characters' => [str_repeat('я', 501), 'STRING', true],
            '501 single-byte characters' => [str_repeat('a', 501), 'text', true],
            'length is counted in characters: 300 characters take 600 bytes' => [str_repeat('я', 300), 'STRING', false],
            'empty string' => ['', 'STRING', false],
            'null' => [null, 'STRING', false],
            'array' => [[], 'STRING', false],
            'type without the length limit' => [str_repeat('я', 501), 'datetime', false],
        ];
    }

    /**
     * @dataProvider convertValueProvider
     *
     * @param mixed $expected
     */
    public function testConvertValue(string $value, $expected): void
    {
        self::assertSame($expected, (new UserCustomFieldsService())->convertValue($value, 'string'));
    }

    public function convertValueProvider(): array
    {
        return [
            'cyrillic string shorter than the limit' => [str_repeat('я', 454), str_repeat('я', 454)],
            'exactly 500 characters' => [str_repeat('я', 500), str_repeat('я', 500)],
            '501 characters is not an empty string' => [str_repeat('я', 501), null],
            'empty string clears the field' => ['', ''],
        ];
    }

    /**
     * @dataProvider customFieldsProvider
     */
    public function testGetCustomFields(string $value, array $expected): void
    {
        $oldMatchedUserFields = ConfigProvider::getMatchedUserFields();
        $oldMatchedUserFields = is_array($oldMatchedUserFields) ? $oldMatchedUserFields : null;

        try {
            $this->setMatchedUserFields([self::BITRIX_FIELD => self::CRM_FIELD]);

            self::assertSame($expected, $this->createService()->getCustomFields([self::BITRIX_FIELD => $value]));
        } finally {
            $this->setMatchedUserFields($oldMatchedUserFields);
        }
    }

    public function customFieldsProvider(): array
    {
        return [
            'cyrillic string shorter than the limit' => [str_repeat('я', 454), [self::CRM_FIELD => str_repeat('я', 454)]],
            'exactly 500 characters' => [str_repeat('я', 500), [self::CRM_FIELD => str_repeat('я', 500)]],
            '501 characters is not sent' => [str_repeat('я', 501), []],
            'empty string clears the field' => ['', [self::CRM_FIELD => '']],
        ];
    }

    public function testGetCustomFieldsLogsTooLongValue(): void
    {
        $value = str_repeat('я', 501);
        $lastLogId = $this->getLastLogId();
        $oldMatchedUserFields = ConfigProvider::getMatchedUserFields();
        $oldMatchedUserFields = is_array($oldMatchedUserFields) ? $oldMatchedUserFields : null;

        try {
            $this->setMatchedUserFields([self::BITRIX_FIELD => self::CRM_FIELD]);
            $this->createService()->getCustomFields(['ID' => 4242, self::BITRIX_FIELD => $value]);
        } finally {
            $this->setMatchedUserFields($oldMatchedUserFields);
        }

        $description = $this->getLogDescription($lastLogId);

        self::assertStringContainsString(UserCustomFieldsService::DIRECTION_TO_CRM, $description);
        self::assertStringContainsString('customer 4242', $description);
        self::assertStringContainsString(self::BITRIX_FIELD, $description);
        self::assertStringContainsString(self::CRM_FIELD, $description);
        self::assertStringContainsString('length: 501', $description);
        self::assertStringContainsString('limit: 500', $description);
        self::assertStringNotContainsString('яя', $description);
    }

    private function createService(): UserCustomFieldsService
    {
        return new class extends UserCustomFieldsService {
            public function getUserFieldTypes(array $fieldNames = []): array
            {
                return array_fill_keys($fieldNames, 'string');
            }
        };
    }

    private function setMatchedUserFields(?array $matchedUserFields): void
    {
        ConfigProvider::setMatchedUserFields($matchedUserFields);
        Helpers::setConfigProperty('mathedCustomFields', $matchedUserFields);
    }

    private function getLastLogId(): int
    {
        global $DB;

        $row = $DB->Query('SELECT MAX(ID) AS ID FROM b_event_log')->Fetch();

        return (int) ($row['ID'] ?? 0);
    }

    private function getLogDescription(int $afterId): string
    {
        global $DB;

        $row = $DB->Query(
            "SELECT DESCRIPTION FROM b_event_log WHERE ID > " . $afterId
            . " AND ITEM_ID = 'CustomFieldLengthExceeded' ORDER BY ID DESC LIMIT 1"
        )->Fetch();

        return (string) ($row['DESCRIPTION'] ?? '');
    }
}
