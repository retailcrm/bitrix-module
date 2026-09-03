<?php

use Intaro\RetailCrm\Service\CustomerService;
use Intaro\RetailCrm\Component\ConfigProvider;
use Tests\Intaro\RetailCrm\Helpers;

/**
 * Class CustomerService
 */
class CustomerServiceTest extends BitrixTestCase
{
    private $customerService;

    public function setUp(): void
    {
        parent::setUp();

        COption::SetOptionString('intaro.retailcrm', 'api_version', 'v5');
        CModule::IncludeModule('intaro.retailcrm');

        $this->customerService = new CustomerService();
    }

    public function testCreateModel()
    {
        $suffix = uniqid();
        $userId = 0;

        try {
            $user = new CUser;
            $userId = (int) $user->Add([
                'LOGIN' => 'test-' . $suffix,
                'NAME' => 'TestName',
                'LAST_NAME' => 'TestLastName',
                'PASSWORD' => 'TestPassword',
                'CONFIRM_PASSWORD' => 'TestPassword',
                'EMAIL' => 'test-' . $suffix . '@example.com',
            ]);

            self::assertGreaterThan(0, $userId, $user->LAST_ERROR);

            $customer = $this->customerService->createModel($userId);
            $fields = CUser::GetByID($userId)->Fetch();
            $dateRegister = new DateTimeImmutable($fields['DATE_REGISTER']);

            self::assertEquals($dateRegister->getTimestamp(), $customer->createdAt->getTimestamp());
        } finally {
            if ($userId > 0) {
                CUser::Delete($userId);
            }
        }
    }

    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function testCreateModelWithMappedCustomUserField(): void
    {
        $fieldName = 'UF_INTARO_CONSENT';
        $crmFieldCode = 'registration_consent';
        $userTypeEntity = new CUserTypeEntity();
        $createdUserField = false;
        $userFieldId = null;
        $userId = 0;
        $oldCustomFieldsStatus = ConfigProvider::getCustomFieldsStatus();
        $oldMatchedUserFields = ConfigProvider::getMatchedUserFields();
        $oldMatchedUserFields = is_array($oldMatchedUserFields) ? $oldMatchedUserFields : null;

        try {
            $userField = CUserTypeEntity::GetList([], ['FIELD_NAME' => $fieldName])->Fetch();

            if (!$userField) {
                $userFieldId = $userTypeEntity->Add([
                    'ENTITY_ID' => 'USER',
                    'FIELD_NAME' => $fieldName,
                    'USER_TYPE_ID' => 'boolean',
                    'MULTIPLE' => 'N',
                    'MANDATORY' => 'N',
                    'SHOW_FILTER' => 'N',
                    'EDIT_IN_LIST' => 'Y',
                    'IS_SEARCHABLE' => 'N',
                    'EDIT_FORM_LABEL' => [
                        'ru' => 'Registration consent test',
                        'en' => 'Registration consent test',
                    ],
                ]);
                $createdUserField = $userFieldId > 0;

                self::assertTrue($createdUserField, (string) $userTypeEntity->LAST_ERROR);
                $GLOBALS['USER_FIELD_MANAGER']->CleanCache();
            }

            ConfigProvider::setCustomFieldsStatus('Y');
            ConfigProvider::setMatchedUserFields([$fieldName => $crmFieldCode]);
            Helpers::setConfigProperty('mathedCustomFields', [$fieldName => $crmFieldCode]);

            $suffix = uniqid();
            $user = new CUser();
            $userId = $user->Add([
                'LOGIN' => 'rcrm-' . $suffix,
                'EMAIL' => 'rcrm-' . $suffix . '@example.com',
                'PASSWORD' => 'TestPassword123',
                'CONFIRM_PASSWORD' => 'TestPassword123',
            ]);

            self::assertGreaterThan(0, $userId, $user->LAST_ERROR);
            self::assertTrue($user->Update($userId, [$fieldName => '1']), $user->LAST_ERROR);

            $by = 'id';
            $order = 'asc';
            $userFields = CUser::GetList(
                $by,
                $order,
                ['ID' => $userId],
                ['SELECT' => [$fieldName]]
            )->Fetch();
            self::assertSame('1', $userFields[$fieldName]);

            $customer = $this->customerService->createModel((int) $userId);

            self::assertSame([$crmFieldCode => 1], $customer->customFields);

            self::assertTrue($user->Update($userId, [$fieldName => '0']), $user->LAST_ERROR);

            $customer = $this->customerService->createModel((int) $userId);

            self::assertSame([$crmFieldCode => 0], $customer->customFields);

            ConfigProvider::setCustomFieldsStatus('N');
            $customer = $this->customerService->createModel((int) $userId);

            self::assertNull($customer->customFields);
        } finally {
            if ($userId > 0) {
                CUser::Delete($userId);
            }

            if ($createdUserField) {
                $userTypeEntity->Delete($userFieldId);
                $GLOBALS['USER_FIELD_MANAGER']->CleanCache();
            }

            ConfigProvider::setCustomFieldsStatus($oldCustomFieldsStatus);
            ConfigProvider::setMatchedUserFields($oldMatchedUserFields);
            Helpers::setConfigProperty('mathedCustomFields', $oldMatchedUserFields);
        }
    }
}
