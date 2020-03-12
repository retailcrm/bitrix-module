<?php
IncludeModuleLangFile(__FILE__);
class RetailCrmCorporateClient
{
    public static $MODULE_ID = 'intaro.retailcrm';
    public static $CRM_SITES_LIST = 'shops-corporate';
    public static $CRM_CORP_NAME = 'nickName-corporate';
    public static $CRM_LEGAL_DETAILS = 'legal_details';
    public static $CRM_DELIVERY_TYPES_ARR = 'deliv_types_arr';
    public static $CRM_CORP_ADDRESS = 'adres-corporate';
    public static $CRM_ORDER_PROPS = 'order_props';

    public static function clientSend($arOrder, $api, $contragentType, $send = false, $fillCorp = false, $site = null)
    {
        if (!$api || empty($contragentType)) {
            return false;
        }

        $address = array();
        $contragent = array();
        $shops = unserialize(COption::GetOptionString(self::$MODULE_ID, self::$CRM_SITES_LIST, 0));
        $corpName = unserialize(COption::GetOptionString(self::$MODULE_ID, self::$CRM_CORP_NAME, 0));
        $corpAdres = unserialize(COption::GetOptionString(self::$MODULE_ID, self::$CRM_CORP_ADDRESS, 0));
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

            if ($prop['CODE'] == $corpAdres) {
                $address = $prop['VALUE'][0];
            }

            if (!empty($optionsLegalDetails)
                && $search = array_search($prop['CODE'], $optionsLegalDetails[$arOrder['PERSON_TYPE_ID']])
            ) {
                $contragent[$search] = $prop['VALUE'][0];//legal order data
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

            if ($fillCorp) {
                $customerCorporate = array_merge(
                    $customerCorporate,
                    array(
                        'customerContacts' => array(
                            array(
                                'isMain' => true,
                                'customer' => array(
                                    'externalId' => $arUser['ID'],
                                    'site' => $shop
                                )
                            )
                        ),
                        'companies' => array(
                            array(
                                'name' => $nickName,
                                'isMain' => true,
                            )
                        ),
                        'addresses' => array(
                            array(
                                'name' => $nickName,
                                'isMain' => true,
                                'text' => $address
                            )
                        )
                    )
                );
            }
        }

        if (isset($customerCorporate)) {
            if ($send && isset($_COOKIE['_rc']) && $_COOKIE['_rc'] != '') {
                $customerCorporate['browserId'] = $_COOKIE['_rc'];
            }

            $normalizer = new RestNormalizer();
            $customerCorporate = $normalizer->normalize($customerCorporate, 'customerCorporate');

            Logger::getInstance()->write($customerCorporate, 'clientCorporate');

            if ($send) {
                $result = RCrmActions::apiMethod($api, 'customersСorporateСreate', __METHOD__, $customerCorporate, $site);
                if (!$result) {
                    return false;
                }

                $customerCorporate['id'] = $result['id'];
            }

            return $customerCorporate;
        }

        return array();
    }
}
