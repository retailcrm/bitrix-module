<?php

use Intaro\RetailCrm\Service\CustomerService;
use Intaro\RetailCrm\Component\ConfigProvider;
use Intaro\RetailCrm\Component\ServiceLocator;
use Intaro\RetailCrm\Model\Api\Customer;
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
    public function testOnAfterUserRegisterWithMappedCustomUserField(): void
    {
        $mappedFieldName = 'UF_INTARO_MAPPED';
        $unmappedFieldName = 'UF_INTARO_CONTROL';
        $crmFieldCode = 'registration_consent';
        $userTypeEntity = new CUserTypeEntity();
        $createdUserFieldIds = [];
        $userId = 0;
        $oldCustomerService = ServiceLocator::get(CustomerService::class);
        $oldCustomFieldsStatus = ConfigProvider::getCustomFieldsStatus();
        $oldMatchedUserFields = ConfigProvider::getMatchedUserFields();
        $oldMatchedUserFields = is_array($oldMatchedUserFields) ? $oldMatchedUserFields : null;

        try {
            foreach ([$mappedFieldName, $unmappedFieldName] as $fieldName) {
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
                            'ru' => $fieldName,
                            'en' => $fieldName,
                        ],
                    ]);

                    self::assertGreaterThan(0, $userFieldId, (string) $userTypeEntity->LAST_ERROR);
                    $createdUserFieldIds[] = $userFieldId;
                }
            }
            $GLOBALS['USER_FIELD_MANAGER']->CleanCache();

            ConfigProvider::setCustomFieldsStatus('Y');
            ConfigProvider::setMatchedUserFields([$mappedFieldName => $crmFieldCode]);
            Helpers::setConfigProperty('mathedCustomFields', [$mappedFieldName => $crmFieldCode]);

            $suffix = uniqid();
            $user = new CUser();
            $userId = $user->Add([
                'LOGIN' => 'rcrm-' . $suffix,
                'EMAIL' => 'rcrm-' . $suffix . '@example.com',
                'PASSWORD' => 'TestPassword123',
                'CONFIRM_PASSWORD' => 'TestPassword123',
                $mappedFieldName => '1',
                $unmappedFieldName => '1',
            ]);

            self::assertGreaterThan(0, $userId, $user->LAST_ERROR);

            $customerService = new class extends CustomerService {
                /** @var Customer|null */
                public $sentCustomer;

                public function createOrUpdateCustomer(Customer $customer)
                {
                    $this->sentCustomer = $customer;

                    return 1;
                }
            };
            ServiceLocator::set(CustomerService::class, $customerService);

            RetailCrmEvent::OnAfterUserRegister([
                'USER_ID' => $userId,
                'UF_REG_IN_PL_INTARO' => 0,
                'UF_AGREE_PL_INTARO' => 0,
                'UF_PD_PROC_PL_INTARO' => 0,
            ]);

            self::assertInstanceOf(Customer::class, $customerService->sentCustomer);
            self::assertSame(
                [$crmFieldCode => 1],
                $customerService->sentCustomer->customFields
            );
            self::assertArrayNotHasKey($unmappedFieldName, $customerService->sentCustomer->customFields);
        } finally {
            if ($userId > 0) {
                CUser::Delete($userId);
            }

            foreach ($createdUserFieldIds as $userFieldId) {
                $userTypeEntity->Delete($userFieldId);
            }
            $GLOBALS['USER_FIELD_MANAGER']->CleanCache();

            ConfigProvider::setCustomFieldsStatus($oldCustomFieldsStatus);
            ConfigProvider::setMatchedUserFields($oldMatchedUserFields);
            Helpers::setConfigProperty('mathedCustomFields', $oldMatchedUserFields);
            ServiceLocator::set(CustomerService::class, $oldCustomerService);
        }
    }
}
