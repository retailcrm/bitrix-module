<?php
IncludeModuleLangFile(__FILE__);
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

        if (empty($arFields['UF_SUBSCRIBE_USER_EMAIL'])) {
            $customer['subscribed'] = false;
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
}
