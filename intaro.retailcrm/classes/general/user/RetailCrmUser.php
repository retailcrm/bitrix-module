<?php

/**
 * @category RetailCRM
 * @package  RetailCRM\User
 * @author   RetailCRM <integration@retailcrm.ru>
 * @license  MIT
 * @link     http://retailcrm.ru
 * @see      http://retailcrm.ru/docs
 */

IncludeModuleLangFile(__FILE__);

use Bitrix\Main\UserTable;
use Throwable;

/**
 * Class RetailCrmUser
 *
 * @category RetailCRM
 * @package RetailCRM\User
 */
class RetailCrmUser
{

    /**
     * @param array $arFields
     * @param       $api
     * @param       $contragentType
     * @param false $send
     * @param null  $site
     *
     * @return array|false
     * @throws \Exception
     */
    public static function customerSend(array $arFields, $api, $contragentType, bool $send = false, $site = null)
    {
        if (!$api || empty($contragentType)) {
            return false;
        }

        if (empty($arFields)) {
            RCrmActions::eventLog('RetailCrmUser::customerSend', 'empty($arFields)', 'incorrect customer');

            return false;
        }

        $customer = self::getSimpleCustomer($arFields);
        $customer['createdAt'] = new \DateTime($arFields['DATE_REGISTER']);
        $customer['contragent'] = ['contragentType' => $contragentType];

        if (RetailcrmConfigProvider::getCustomFieldsStatus() === 'Y') {
            $customer['customFields'] = self::getCustomFields($arFields);
        }

        if ($send && isset($_COOKIE['_rc']) && $_COOKIE['_rc'] != '') {
            $customer['browserId'] = $_COOKIE['_rc'];
        }

        if (function_exists('retailCrmBeforeCustomerSend')) {
            $newResCustomer = retailCrmBeforeCustomerSend($customer);

            if (is_array($newResCustomer) && !empty($newResCustomer)) {
                $customer = $newResCustomer;
            } elseif ($newResCustomer === false) {
                RCrmActions::eventLog('RetailCrmUser::customerSend', 'retailCrmBeforeCustomerSend()', 'UserID = ' . $arFields['ID'] . '. Sending canceled after retailCrmBeforeCustomerSend');

                return false;
            }
        }

        $normalizer = new RestNormalizer();
        $customer = $normalizer->normalize($customer, 'customers');

        if (array_key_exists('UF_SUBSCRIBE_USER_EMAIL', $arFields)) {
            // UF_SUBSCRIBE_USER_EMAIL = '1' or '0'
            $customer['subscribed'] = (bool) $arFields['UF_SUBSCRIBE_USER_EMAIL'];
        }

        Logger::getInstance()->write($customer, 'customerSend');

        if (
            $send
            && !RCrmActions::apiMethod($api, 'customersCreate', __METHOD__, $customer, $site)
        ) {
                return false;
        }

        return $customer;
    }

    public static function customerEdit($arFields, $api, $optionsSitesList = array()) : bool
    {
        if (empty($arFields)) {
            RCrmActions::eventLog('RetailCrmUser::customerEdit', 'empty($arFields)', 'incorrect customer');
            return false;
        }

        $customer = self::getSimpleCustomer($arFields);
        $found = false;

        if (RetailcrmConfigProvider::getCustomFieldsStatus() === 'Y') {
            $customer['customFields'] = self::getCustomFields($arFields);
        }

        if (count($optionsSitesList) > 0) {
            foreach ($optionsSitesList as $site) {
                $userCrm = RCrmActions::apiMethod($api, 'customersGet', __METHOD__, $arFields['ID'], $site);
                if (isset($userCrm['customer'])) {
                    $found = true;
                    break;
                }
            }
        } else {
            $site = null;
            $userCrm = RCrmActions::apiMethod($api, 'customersGet', __METHOD__, $arFields['ID'], $site);
            if (isset($userCrm['customer'])) {
                $found = true;
            }
        }

        if ($found) {
            $normalizer = new RestNormalizer();
            $customer = $normalizer->normalize($customer, 'customers');
            $customer = self::getBooleanFields($customer, $arFields);

            if (function_exists('retailCrmBeforeCustomerSend')) {
                $newResCustomer = retailCrmBeforeCustomerSend($customer);
                if (is_array($newResCustomer) && !empty($newResCustomer)) {
                    $customer = $newResCustomer;
                } elseif ($newResCustomer === false) {
                    RCrmActions::eventLog('RetailCrmUser::customerEdit', 'retailCrmBeforeCustomerSend()', 'UserID = ' . $arFields['ID'] . '. Sending canceled after retailCrmBeforeCustomerSend');

                    return false;
                }
            }

            Logger::getInstance()->write($customer, 'customerSend');

            RCrmActions::apiMethod($api, 'customersEdit', __METHOD__, $customer, $site);
        }

        return true;
    }

    /**
     * @param array $arFields
     *
     * @return array
     */
    private static function getSimpleCustomer(array $arFields): array
    {
        $customer['externalId'] = $arFields['ID'];
        $customer['firstName'] = $arFields['NAME'] ?? null;
        $customer['lastName'] = $arFields['LAST_NAME'] ?? null;
        $customer['patronymic'] = $arFields['SECOND_NAME'] ?? null;
        $customer['phones'][]['number'] = $arFields['PERSONAL_PHONE'] ?? null;
        $customer['phones'][]['number'] = $arFields['WORK_PHONE'] ?? null;
        $customer['address']['city'] = $arFields['PERSONAL_CITY'] ?? null;
        $customer['address']['text'] = $arFields['PERSONAL_STREET'] ?? null;
        $customer['address']['index'] = $arFields['PERSONAL_ZIP'] ?? null;

        if (mb_strlen($arFields['EMAIL']) < 100) {
            $customer['email'] = $arFields['EMAIL'];
        }

        return $customer;
    }

    private static function getBooleanFields($customer, $arFields)
    {
        if (isset($arFields['UF_SUBSCRIBE_USER_EMAIL'])) {
            if ($arFields['UF_SUBSCRIBE_USER_EMAIL'] === "1") {
                $customer['subscribed'] = true;
            } else {
                $customer['subscribed'] = false;
            }
        }

        return $customer;
    }

    private static function getCustomFields(array $arFields)
    {
        if (!method_exists(RCrmActions::class, 'getTypeUserField')
            || !method_exists(RCrmActions::class, 'convertCmsFieldToCrmValue')
        ) {
            return [];
        }

        $customUserFields = RetailcrmConfigProvider::getMatchedUserFields();
        $typeList = RCrmActions::getTypeUserField();
        $result = [];

        foreach ($customUserFields as $code => $codeCrm) {
            if (isset($arFields[$code])) {
                $type = $typeList[$code] ?? '';
                $result[$codeCrm] = RCrmActions::convertCmsFieldToCrmValue($arFields[$code], $type);
            }
        }

        return $result;
    }

    public static function fixDateCustomer(): void
    {
        CAgent::RemoveAgent("RetailCrmUser::fixDateCustomer();", RetailcrmConstants::MODULE_ID);
        COption::SetOptionString(RetailcrmConstants::MODULE_ID, RetailcrmConstants::OPTION_FIX_DATE_CUSTOMER, 'Y');

        $startId = COption::GetOptionInt(RetailcrmConstants::MODULE_ID, RetailcrmConstants::OPTION_FIX_DATE_CUSTOMER_LAST_ID, 0);
        $api = new RetailCrm\ApiClient(RetailcrmConfigProvider::getApiUrl(), RetailcrmConfigProvider::getApiKey());
        $optionsSitesList = RetailcrmConfigProvider::getSitesList();
        $limit = 50;
        $offset = 0;

        while(true) {
            try {
                $usersResult = UserTable::getList([
                    'select' => ['ID', 'DATE_REGISTER', 'LID'],
                    'filter' => ['>ID' => $startId],
                    'order' => ['ID'],
                    'limit' => $limit,
                    'offset' => $offset,
                ]);
            } catch (\Throwable $exception) {
                Logger::getInstance()->write($exception->getMessage(), 'fixDateCustomers');

                break;
            }

            $users = $usersResult->fetchAll();

            if ($users === []) {
                break;
            }

            foreach ($users as $user) {
                $site = null;

                if ($optionsSitesList) {
                    if (isset($user['LID']) && array_key_exists($user['LID'], $optionsSitesList) && $optionsSitesList[$user['LID']] !== null) {
                        $site = $optionsSitesList[$user['LID']];
                    } else {
                        continue;
                    }
                }

                $customer['externalId'] = $user['ID'];

                try {
                    $date = new \DateTime($user['DATE_REGISTER']);
                    $customer['createdAt'] = $date->format('Y-m-d H:i:s');

                    RCrmActions::apiMethod($api, 'customersEdit', __METHOD__, $customer, $site);
                } catch (\Throwable $exception) {
                    Logger::getInstance()->write($exception->getMessage(), 'fixDateCustomers');
                    continue;
                }

                time_nanosleep(0, 250000000);
            }

            COption::SetOptionInt(RetailcrmConstants::MODULE_ID, RetailcrmConstants::OPTION_FIX_DATE_CUSTOMER_LAST_ID, end($users)['ID']);

            $offset += $limit;
        }
    }

    public static function updateLoyaltyAccountIds(): bool
    {
        $api = new RetailCrm\ApiClient(RetailcrmConfigProvider::getApiUrl(), RetailcrmConfigProvider::getApiKey());
        $offset = 0;
        $limit = 50;
        $optionsSitesList = RetailcrmConfigProvider::getSitesList();
        $status = true;

        while (true) {
            try {
                $usersResult = UserTable::getList([
                    'select' => ['ID', 'UF_REG_IN_PL_INTARO', 'LID', 'UF_LP_ID_INTARO'],
                    'filter' => ['=UF_REG_IN_PL_INTARO' => true],
                    'order' => ['ID'],
                    'limit' => $limit,
                    'offset' => $offset
                ]);
            } catch (\Exception $exception) {
                Logger::getInstance()->write($exception->getMessage(), 'loyaltyIdsUpdate');

                $status = false;

                break;
            }

            $users = $usersResult->fetchAll();

            if ($users === []) {
                break;
            }

            $offset += $limit;

            foreach ($users as $user) {
                $site = null;

                if ($optionsSitesList) {
                    if (isset($user['LID']) && array_key_exists($user['LID'], $optionsSitesList) && $optionsSitesList[$user['LID']] !== null) {
                        $site = $optionsSitesList[$user['LID']];
                    } else {
                        continue;
                    }
                }

                $filter['customerExternalId'] = $user['ID'];

                try {
                    $actualLoyalty = null;
                    $crmAccounts = RCrmActions::apiMethod($api, 'getLoyaltyAccounts', __METHOD__, $filter, $site);
                    
                    foreach ($crmAccounts['loyaltyAccounts'] as $crmAccount) {
                        $loyalty = $crmAccounts = RCrmActions::apiMethod(
                            $api,
                            'getLoyaltyLoyalty',
                            __METHOD__,
                            $crmAccount['loyalty']['id'],
                            $site
                        );

                        if ($loyalty['loyalty']['active'] === true) {
                            $actualLoyalty = $crmAccount;

                            break;
                        }
                    }

                    if ($actualLoyalty !== null && $user['UF_LP_ID_INTARO'] !== $actualLoyalty['id']) {
                        $updateUser = new CUser;
                        $cardNumber = isset($actualLoyalty['cardNumber']) ? $actualLoyalty['cardNumber'] : '';

                        $fields = [
                            "UF_LP_ID_INTARO" => $actualLoyalty['id'],
                            "UF_CARD_NUM_INTARO" => $cardNumber
                        ];

                        if ($updateUser->Update($user['ID'], $fields)) {
                            Logger::getInstance()->write(
                                sprintf('Loyalty account ID for user with id %s updated', $user['ID']),
                                'loyaltyIdsUpdate'
                            );
                        }
                    }
                } catch (Throwable $exception) {
                    Logger::getInstance()->write($exception->getMessage(), 'loyaltyIdsUpdate');

                    continue;
                }

                time_nanosleep(0, 550000000);
            }
        }

        return $status;
    }
}
