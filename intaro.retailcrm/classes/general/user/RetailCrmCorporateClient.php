<?php
IncludeModuleLangFile(__FILE__);
class RetailCrmCorporateClient
{
    public static $MODULE_ID = 'intaro.retailcrm';
    public static $CRM_SITES_LIST = 'shops-corporate';
    public static $CRM_CORP_NAME = 'nickName-corporate';
    public static $CRM_LEGAL_DETAILS = 'legal_details';
    public static $CRM_DELIVERY_TYPES_ARR = 'deliv_types_arr';
    public static $CRM_CORP_ADRES = 'adres-corporate';
    public static $CRM_ORDER_PROPS = 'order_props';

    public static function clientSend($arOrder, $api, $contragentType, $send = false, $site = null)
    {
        if (!$api || empty($contragentType)) {
            return false;
        }
        $shops = unserialize(COption::GetOptionString(self::$MODULE_ID, self::$CRM_SITES_LIST, 0));
        $corpName = unserialize(COption::GetOptionString(self::$MODULE_ID, self::$CRM_CORP_NAME, 0));
        $corpAdres = unserialize(COption::GetOptionString(self::$MODULE_ID, self::$CRM_CORP_ADRES, 0));
        $arUser = Bitrix\Main\UserTable::getById($arOrder['USER_ID'])->fetch();
        $optionsLegalDetails = unserialize(COption::GetOptionString(self::$MODULE_ID, self::$CRM_LEGAL_DETAILS, 0));

        if (count($shops) == 0) {
            RCrmActions::eventLog('RetailCrmCorporateClient::clientSend()', '$shops', 'No stores selected for download');

            return false;
        }

        foreach ($arOrder['PROPS']['properties'] as $prop) {
            if ($prop['CODE'] == $corpName) {
                $nickName = $prop['VALUE'][0];
            }
        }

        if (empty($nickName)) {
            $nickName = $arUser['WORK_COMPANY'];
        }

        if (!empty($contragentType)) {
            $contragent['contragentType'] = $contragentType;
        }

        foreach ($shops as $shop) {
            $customerCorporate = array(
                'createdAt'      => $arOrder['DATE_INSERT'],
                "nickName" => $nickName,
            );
        }

        if ($customerCorporate) {
            if ($send && isset($_COOKIE['_rc']) && $_COOKIE['_rc'] != '') {
                $customerCorporate['browserId'] = $_COOKIE['_rc'];
            }

            $normalizer = new RestNormalizer();
            $customerCorporate = $normalizer->normalize($customerCorporate, 'customerCorporate');

            $log = new Logger();
            $log->write($customerCorporate, 'clientCorporate');

            if ($send) {
                $result = RCrmActions::apiMethod($api, 'customersСorporateСreate', __METHOD__, $customerCorporate, $site);
                if (!$result) {
                    return false;
                }

                $customerCorporate['id'] = $result['id'];
            }

            return $customerCorporate;
        }
    }
}
