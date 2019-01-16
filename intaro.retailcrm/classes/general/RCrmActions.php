<?php
IncludeModuleLangFile(__FILE__);
class RCrmActions
{
    public static $MODULE_ID = 'intaro.retailcrm';
    public static $CRM_ORDER_FAILED_IDS = 'order_failed_ids';
    public static $CRM_API_VERSION = 'api_version';

    const CANCEL_PROPERTY_CODE = 'INTAROCRM_IS_CANCELED';

    public static function SitesList()
    {
        $arSites = array();
        $rsSites = CSite::GetList($by, $sort, array('ACTIVE' => 'Y'));
        while ($ar = $rsSites->Fetch()) {
            $arSites[] = $ar;
        }

        return $arSites;
    }

    public static function OrderTypesList($arSites)
    {
        $orderTypesList = array();
        foreach ($arSites as $site) {
            $personTypes = \Bitrix\Sale\PersonType::load($site['LID']);
            $bitrixOrderTypesList = array();
            foreach ($personTypes as $personType) {
                if (!array_key_exists($personType['ID'], $orderTypesList)) {
                    $bitrixOrderTypesList[$personType['ID']] = $personType;
                }
                asort($bitrixOrderTypesList);
            }
            $orderTypesList = $orderTypesList + $bitrixOrderTypesList;
        }

        return $orderTypesList;
    }

    public static function DeliveryList()
    {
        $bitrixDeliveryTypesList = array();
        $arDeliveryServiceAll = \Bitrix\Sale\Delivery\Services\Manager::getActiveList();
        $noOrderId = \Bitrix\Sale\Delivery\Services\EmptyDeliveryService::getEmptyDeliveryServiceId();
        $groups = array();
        foreach ($arDeliveryServiceAll as $arDeliveryService) {
            if ($arDeliveryService['CLASS_NAME'] == '\Bitrix\Sale\Delivery\Services\Group') {
                $groups[] = $arDeliveryService['ID'];
            }
        }
        foreach ($arDeliveryServiceAll as $arDeliveryService) {
            if ((($arDeliveryService['PARENT_ID'] == '0' || $arDeliveryService['PARENT_ID'] == null) ||
                        in_array($arDeliveryService['PARENT_ID'], $groups)) &&
                    $arDeliveryService['ID'] != $noOrderId &&
                    $arDeliveryService['CLASS_NAME'] != '\Bitrix\Sale\Delivery\Services\Group') {
                if (in_array($arDeliveryService['PARENT_ID'], $groups)) {
                    $arDeliveryService['PARENT_ID'] = 0;
                }
                $bitrixDeliveryTypesList[] = $arDeliveryService;
            }
        }

        return $bitrixDeliveryTypesList;
    }

    public static function PaymentList()
    {
        $bitrixPaymentTypesList = array();
        $dbPaymentAll = \Bitrix\Sale\PaySystem\Manager::getList(array(
            'select' => array('ID', 'NAME'),
            'filter' => array('ACTIVE' => 'Y')
        ));
        while ($payment = $dbPaymentAll->fetch()) {
            $bitrixPaymentTypesList[] = $payment;
        }

        return $bitrixPaymentTypesList;
    }

    public static function StatusesList()
    {
        $bitrixPaymentStatusesList = array();
        $obStatuses = \Bitrix\Sale\Internals\StatusTable::getList(array(
            'filter' => array('TYPE' => 'O', '=Bitrix\Sale\Internals\StatusLangTable:STATUS.LID' => LANGUAGE_ID),
            'select' => array('ID', "NAME" => 'Bitrix\Sale\Internals\StatusLangTable:STATUS.NAME')
        ));
        while ($arStatus = $obStatuses->fetch()) {
            $bitrixPaymentStatusesList[$arStatus['ID']] = array(
                'ID'   => $arStatus['ID'],
                'NAME' => $arStatus['NAME'],
            );
        }

        return $bitrixPaymentStatusesList;
    }

    public static function OrderPropsList()
    {
        $bitrixPropsList = array();
        $arPropsAll = \Bitrix\Sale\Internals\OrderPropsTable::getList(array(
            'select' => array('*'),
            'filter' => array('CODE' => '_%')
        ));
        while ($prop = $arPropsAll->Fetch()) {
            $bitrixPropsList[$prop['PERSON_TYPE_ID']][] = $prop;
        }

        return $bitrixPropsList;
    }

    public static function PricesExportList()
    {
        $catalogExportPrices = array();
        $dbPriceType = CCatalogGroup::GetList(
            array(),
            array(),
            false,
            false,
            array('ID', 'NAME', 'NAME_LANG')
        );

        while ($arPriceType = $dbPriceType->Fetch())
        {
            $catalogExportPrices[$arPriceType['ID']] = $arPriceType;
        }

        return $catalogExportPrices;
    }

    public static function StoresExportList()
    {
        $catalogExportStores = array();
        $dbStores = CCatalogStore::GetList(array(), array("ACTIVE" => "Y"), false, false, array('ID', 'TITLE'));
        while ($stores = $dbStores->Fetch()) {
            $catalogExportStores[] = $stores;
        }

        return $catalogExportStores;
    }

    public static function IblocksExportList()
    {
        $catalogExportIblocks = array();
        $dbIblocks = CIBlock::GetList(array("IBLOCK_TYPE" => "ASC", "NAME" => "ASC"), array('CHECK_PERMISSIONS' => 'Y','MIN_PERMISSION' => 'W'));
        while ($iblock = $dbIblocks->Fetch()) {
            if ($arCatalog = CCatalog::GetByIDExt($iblock["ID"])) {
                if($arCatalog['CATALOG_TYPE'] == "D" || $arCatalog['CATALOG_TYPE'] == "X" || $arCatalog['CATALOG_TYPE'] == "P") {
                    $catalogExportIblocks[$iblock['ID']] = array(
                        'ID' => $iblock['ID'],
                        'IBLOCK_TYPE_ID' => $iblock['IBLOCK_TYPE_ID'],
                        'LID' => $iblock['LID'],
                        'CODE' => $iblock['CODE'],
                        'NAME' => $iblock['NAME'],
                    );

                    if ($arCatalog['CATALOG_TYPE'] == "X" || $arCatalog['CATALOG_TYPE'] == "P") {
                        $iblockOffer = CCatalogSKU::GetInfoByProductIBlock($iblock["ID"]);
                        $catalogExportIblocks[$iblock['ID']]['SKU'] = $iblockOffer;
                    }
                }
            }
        }

        return $catalogExportIblocks;
    }

    /**
     *
     * w+ event in bitrix log
     */

    public static function eventLog($auditType, $itemId, $description)
    {
        CEventLog::Add(array(
            "SEVERITY"      => "SECURITY",
            "AUDIT_TYPE_ID" => $auditType,
            "MODULE_ID"     => self::$MODULE_ID,
            "ITEM_ID"       => $itemId,
            "DESCRIPTION"   => $description,
        ));
    }

    /**
     *
     * Agent function
     *
     * @return self name
     */

    public static function uploadOrdersAgent()
    {
        RetailCrmOrder::uploadOrders();
        $failedIds = unserialize(COption::GetOptionString(self::$MODULE_ID, self::$CRM_ORDER_FAILED_IDS, 0));
        if (is_array($failedIds) && !empty($failedIds)) {
            RetailCrmOrder::uploadOrders(50, true);
        }

        return;
    }

    /**
     *
     * Agent function
     *
     * @return self name
     */

    public static function orderAgent()
    {
        if (COption::GetOptionString('main', 'agents_use_crontab', 'N') != 'N') {
            define('NO_AGENT_CHECK', true);
        }

        RetailCrmHistory::customerHistory();
        RetailCrmHistory::orderHistory();
        self::uploadOrdersAgent();

        return 'RCrmActions::orderAgent();';
    }

    /**
     * removes all empty fields from arrays
     * working with nested arrs
     *
     * @param array $arr
     * @return array
     */
    public static function clearArr($arr)
    {
        if (is_array($arr) === false) {
            return $arr;
        }

        $result = array();
        foreach ($arr as $index => $node ) {
            $result[ $index ] = is_array($node) === true ? self::clearArr($node) : trim($node);
            if ($result[ $index ] == '' || $result[ $index ] === null || count($result[ $index ]) < 1) {
                unset($result[ $index ]);
            }
        }

        return $result;
    }

    /**
     *
     * @global $APPLICATION
     * @param $str in SITE_CHARSET
     * @return  $str in utf-8
     */
    public static function toJSON($str)
    {
        global $APPLICATION;

        return $APPLICATION->ConvertCharset($str, SITE_CHARSET, 'utf-8');
    }

    /**
     *
     * @global $APPLICATION
     * @param $str in utf-8
     * @return $str in SITE_CHARSET
     */
    public static function fromJSON($str)
    {
        global $APPLICATION;

        return $APPLICATION->ConvertCharset($str, 'utf-8', SITE_CHARSET);
    }

    /**
     * Unserialize array
     *
     * @param string $string
     *
     * @return mixed
     */
    public static function unserializeArrayRecursive($string)
    {
        if ($string === false || empty($string)) {
            return false;
        }

        if (is_string($string)) {
            $string = unserialize($string);
        }

        if (!is_array($string)) {
            $string = self::unserializeArrayRecursive($string);
        }

        return $string;
    }

    public static function explodeFIO($fio)
    {
        $result = array();
        $fio = preg_replace('|[\s]+|s', ' ', trim($fio));
        if (empty($fio)) {
            return $result;
        } else {
            $newFio = explode(" ", $fio, 3);
        }

        switch (count($newFio)) {
            default:
            case 0:
                $result['firstName']  = $fio;
                break;
            case 1:
                $result['firstName']  = $newFio[0];
                break;
            case 2:
                $result = array(
                    'lastName'  => $newFio[0],
                    'firstName' => $newFio[1]
                );
                break;
            case 3:
                $result = array(
                    'lastName'   => $newFio[0],
                    'firstName'  => $newFio[1],
                    'patronymic' => $newFio[2]
                );
                break;
        }

        return $result;
    }

    public static function sendConfiguration($api, $api_version, $active = true)
    {
        $scheme = isset($_SERVER['HTTPS']) ? 'https://' : 'http://';
        $baseUrl = $scheme . $_SERVER['HTTP_HOST'];
        $integrationCode = 'bitrix';
        $logo = 'https://s3.eu-central-1.amazonaws.com/retailcrm-billing/images/5af47fe682bf2-1c-bitrix-logo.svg';
        $accountUrl = $baseUrl . '/bitrix/admin';

        $clientId = COption::GetOptionString(self::$MODULE_ID, 'client_id', 0);

        if (!$clientId) {
            $clientId = uniqid();
            COption::SetOptionString(self::$MODULE_ID, 'client_id', $clientId);
        }

        $code = $integrationCode . '-' . $clientId;

        if ($api_version == 'v4') {
            $configuration = array(
                'name' => GetMessage('API_MODULE_NAME'),
                'code' => $code,
                'logo' => $logo,
                'configurationUrl' => $accountUrl,
                'active' => $active
            );

            self::apiMethod($api, 'marketplaceSettingsEdit', __METHOD__, $configuration);
        } else {
            $configuration = array(
                'clientId' => $clientId,
                'code' => $code,
                'integrationCode' => $integrationCode,
                'active' => $active,
                'name' => GetMessage('API_MODULE_NAME'),
                'logo' => $logo,
                'baseUrl' => $baseUrl,
                'accountUrl' => $accountUrl
            );

            self::apiMethod($api, 'integrationModulesEdit', __METHOD__, $configuration);
        }
    }

    public static function apiMethod($api, $methodApi, $method, $params, $site = null)
    {
        switch ($methodApi) {
            case 'ordersPaymentDelete':
            case 'ordersHistory':
            case 'customerHistory':
            case 'ordersFixExternalIds':
            case 'customersFixExternalIds':
                return self::proxy($api, $methodApi, $method, array($params));

            case 'orderGet':
                return self::proxy($api, 'ordersGet', $method, array($params, 'id', $site));

            case 'ordersGet':
            case 'ordersEdit':
            case 'customersGet':
            case 'customersEdit':
                return self::proxy($api, $methodApi, $method, array($params, 'externalId', $site));

            case 'paymentEditById':
                return self::proxy($api, 'ordersPaymentEdit', $method, array($params, 'id', $site));

            case 'paymentEditByExternalId':
                return self::proxy($api, 'ordersPaymentEdit', $method, array($params, 'externalId', $site));

            default:
                return self::proxy($api, $methodApi, $method, array($params, $site));
        }
    }

    private static function proxy($api, $methodApi, $method, $params) {
        $log = new Logger();
        $version = COption::GetOptionString(self::$MODULE_ID, self::$CRM_API_VERSION, 0);
        try {
            $result = call_user_func_array(array($api, $methodApi), $params);

            if ($result->getStatusCode() !== 200 && $result->getStatusCode() !== 201) {
                if ($methodApi == 'ordersGet' || $methodApi == 'customersGet') {
                    $log->write(array(
                        'api' => $version,
                        'methodApi' => $methodApi,
                        'errorMsg' => !empty($result['errorMsg']) ? $result['errorMsg'] : '',
                        'errors' => !empty($result['errors']) ? $result['errors'] : '',
                        'params' => $params
                    ), 'apiErrors');
                } elseif ($methodApi == 'customersUpload' || $methodApi == 'ordersUpload') {
                    $log->write(array(
                        'api' => $version,
                        'methodApi' => $methodApi,
                        'errorMsg' => !empty($result['errorMsg']) ? $result['errorMsg'] : '',
                        'errors' => !empty($result['errors']) ? $result['errors'] : '',
                        'params' => $params
                    ), 'uploadApiErrors');
                } else {
                    self::eventLog(__CLASS__ . '::' . $method, 'RetailCrm\ApiClient::' . $methodApi, !empty($result['errorMsg']) ? $result['errorMsg'] : '');
                    $log->write(array(
                        'api' => $version,
                        'methodApi' => $methodApi,
                        'errorMsg' => !empty($result['errorMsg']) ? $result['errorMsg'] : '',
                        'errors' => !empty($result['errors']) ? $result['errors'] : '',
                        'params' => $params
                    ), 'apiErrors');
                }

                if (function_exists('retailCrmApiResult')) {
                    retailCrmApiResult($methodApi, false, $result->getStatusCode());
                }

                if ($result->getStatusCode() == 460) {
                    return true;
                }

                return false;
            }
        } catch (\RetailCrm\Exception\CurlException $e) {
            self::eventLog(
                __CLASS__ . '::' . $method, 'RetailCrm\ApiClient::' . $methodApi . '::CurlException',
                $e->getCode() . ': ' . $e->getMessage()
            );
            $log->write(array(
                'api' => $version,
                'methodApi' => $methodApi,
                'errorMsg' => $e->getMessage(),
                'errors' => $e->getCode(),
                'params' => $params
            ), 'apiErrors');

            if (function_exists('retailCrmApiResult')) {
                retailCrmApiResult($methodApi, false, 'CurlException');
            }

            return false;
        } catch (InvalidArgumentException $e) {
            self::eventLog(
                __CLASS__ . '::' . $method, 'RetailCrm\ApiClient::' . $methodApi . '::InvalidArgumentException',
                $e->getCode() . ': ' . $e->getMessage()
            );
            $log->write(array(
                'api' => $version,
                'methodApi' => $methodApi,
                'errorMsg' => $e->getMessage(),
                'errors' => $e->getCode(),
                'params' => $params
            ), 'apiErrors');

            if (function_exists('retailCrmApiResult')) {
                retailCrmApiResult($methodApi, false, 'ArgumentException');
            }

            return false;
        } catch (\RetailCrm\Exception\InvalidJsonException $e) {
            self::eventLog(
                __CLASS__ . '::' . $method, 'RetailCrm\ApiClient::' . $methodApi . '::InvalidJsonException',
                $e->getCode() . ': ' . $e->getMessage()
            );
            $log->write(array(
                'api' => $version,
                'methodApi' => $methodApi,
                'errorMsg' => $e->getMessage(),
                'errors' => $e->getCode(),
                'params' => $params
            ), 'apiErrors');

            if (function_exists('retailCrmApiResult')) {
                retailCrmApiResult($methodApi, false, 'ArgumentException');
            }
        }

        if (function_exists('retailCrmApiResult')) {
            retailCrmApiResult($methodApi, true, $result->getStatusCode());
        }

        return isset($result) ? $result : false;
    }
}
