<?php

use Intaro\RetailCrm\Service\CustomerService;

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
        $user = new CUser;
        $arUser = $user->Register(
            'TestLogin',
            'TestName',
            'TestLastName',
            'TestPassword',
            'TestPassword',
            'testemail@gmail.com'
        );

        $customer = $this->customerService->createModel($arUser['ID']);
        $fields = CUser::GetByID($arUser['ID'])->Fetch();
        $dateRegister = new DateTimeImmutable($fields['DATE_REGISTER']);

        self::assertEquals($dateRegister->getTimestamp(), $customer->createdAt->getTimestamp());
        CUser::Delete($arUser['ID']);
    }
}
