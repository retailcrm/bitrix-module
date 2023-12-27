<?php

use Bitrix\Sale\PersonType;
use Intaro\RetailCrm\Component\ServiceLocator;
use Bitrix\Sale\Delivery\Services\EmptyDeliveryService;
use Bitrix\Sale\Internals\OrderPropsTable;
use Bitrix\Sale\Internals\StatusTable;
use Bitrix\Sale\PaySystem\Manager;
use Intaro\RetailCrm\Service\Utils;
use RetailCrm\Exception\CurlException;
use RetailCrm\Exception\InvalidJsonException;
use Intaro\RetailCrm\Service\ManagerService;
use Bitrix\Main\UserFieldTable;
use Bitrix\Main\UserFieldLangTable;
use Bitrix\Sale\Internals\SiteCurrencyTable;

IncludeModuleLangFile(__FILE__);

require_once __DIR__ . '/../../lib/component/servicelocator.php';
require_once __DIR__ . '/../../lib/service/utils.php';

class RCrmActions
{
    public static $MODULE_ID = 'intaro.retailcrm';
    public static $CRM_ORDER_FAILED_IDS = 'order_failed_ids';
    public static $CRM_API_VERSION = 'api_version';

    public static function getCurrencySites(): array
    {
        $sites = self::getSitesList();
        $baseCurrency = CCurrency::GetBaseCurrency();
        $sitesCurrency = [];

        foreach ($sites as $site) {
            $siteCurrency = SiteCurrencyTable::getCurrency($site['LID']);

            $sitesCurrency[$site['LID']] = !empty($siteCurrency['CURRENCY'])
                ? $siteCurrency['CURRENCY']
                : $baseCurrency;
        }

        return $sitesCurrency;
    }

    /**
     * @return array
     */
    public static function getSitesList(): array
    {
        $arSites = [];
        $rsSites = CSite::GetList($by, $sort, ['ACTIVE' => 'Y']);

        while ($ar = $rsSites->Fetch()) {
            $arSites[] = $ar;
        }

        return $arSites;
    }

    public static function OrderTypesList($arSites)
    {
        $orderTypesList = [];

        foreach ($arSites as $site) {
            $personTypes = PersonType::load($site['LID']);
            $bitrixOrderTypesList = [];

            foreach ($personTypes as $personType) {
                if (!array_key_exists($personType['ID'], $orderTypesList)) {
                    $bitrixOrderTypesList[$personType['ID']] = $personType;
                }

                asort($bitrixOrderTypesList);
            }

            $orderTypesList += $bitrixOrderTypesList;
        }

        return $orderTypesList;
    }

    public static function DeliveryList()
    {
        $bitrixDeliveryTypesList = [];
        $arDeliveryServiceAll = \Bitrix\Sale\Delivery\Services\Manager::getActiveList();
        $noOrderId = EmptyDeliveryService::getEmptyDeliveryServiceId();
        $groups = [];

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
        $bitrixPaymentTypesList = [];
        $dbPaymentAll = Manager::getList(['select' => ['ID', 'NAME'], 'filter' => ['ACTIVE' => 'Y']]);

        while ($payment = $dbPaymentAll->fetch()) {
            $bitrixPaymentTypesList[] = $payment;
        }

        return $bitrixPaymentTypesList;
    }

    public static function StatusesList()
    {
        $bitrixPaymentStatusesList = [];
        $obStatuses = StatusTable::getList([
            'filter' => ['TYPE' => 'O', '=Bitrix\Sale\Internals\StatusLangTable:STATUS.LID' => LANGUAGE_ID],
            'select' => ['ID', 'NAME' => 'Bitrix\Sale\Internals\StatusLangTable:STATUS.NAME'],
        ]);

        while ($arStatus = $obStatuses->fetch()) {
            $bitrixPaymentStatusesList[$arStatus['ID']] = ['ID'   => $arStatus['ID'], 'NAME' => $arStatus['NAME']];
        }

        return $bitrixPaymentStatusesList;
    }

    public static function OrderPropsList()
    {
        $bitrixPropsList = [];
        $arPropsAll = OrderPropsTable::getList([
            'select' => ['*'],
            'filter' => [
                ['CODE' => '_%'],
                ['!=TYPE' => 'LOCATION']
            ]
        ]);

        while ($prop = $arPropsAll->Fetch()) {
            $bitrixPropsList[$prop['PERSON_TYPE_ID']][] = $prop;
        }

        return $bitrixPropsList;
    }

    public static function getLocationProps()
    {
        $bitrixPropsList = [];
        $arPropsAll = OrderPropsTable::getList([
            'select' => ['*'],
            'filter' => [
                ['CODE' => '_%'],
                ['TYPE' => 'LOCATION']
            ]
        ]);

        while ($prop = $arPropsAll->Fetch()) {
            $bitrixPropsList[$prop['PERSON_TYPE_ID']][] = $prop;
        }

        return $bitrixPropsList;
    }

    public static function PricesExportList()
    {
        $catalogExportPrices = [];
        $dbPriceType = CCatalogGroup::GetList([], [], false, false, ['ID', 'NAME', 'NAME_LANG']);

        while ($arPriceType = $dbPriceType->Fetch())
        {
            $catalogExportPrices[$arPriceType['ID']] = $arPriceType;
        }

        return $catalogExportPrices;
    }

    public static function StoresExportList()
    {
        $catalogExportStores = [];
        $dbStores = CCatalogStore::GetList([], ['ACTIVE' => 'Y'], false, false, ['ID', 'TITLE']);

        while ($stores = $dbStores->Fetch()) {
            $catalogExportStores[] = $stores;
        }

        return $catalogExportStores;
    }

    public static function IblocksExportList()
    {
        $catalogExportIblocks = [];
        $dbIblocks = CIBlock::GetList(['IBLOCK_TYPE' => 'ASC', 'NAME' => 'ASC'], ['CHECK_PERMISSIONS' => 'Y', 'MIN_PERMISSION' => 'W']);

        while ($iblock = $dbIblocks->Fetch()) {
            if ($arCatalog = CCatalog::GetByIDExt($iblock['ID'])) {
                if($arCatalog['CATALOG_TYPE'] == 'D' || $arCatalog['CATALOG_TYPE'] == 'X' || $arCatalog['CATALOG_TYPE'] == 'P') {
                    $catalogExportIblocks[$iblock['ID']] = [
                        'ID' => $iblock['ID'],
                        'IBLOCK_TYPE_ID' => $iblock['IBLOCK_TYPE_ID'],
                        'LID' => $iblock['LID'],
                        'CODE' => $iblock['CODE'],
                        'NAME' => $iblock['NAME'],
                    ];

                    if ($arCatalog['CATALOG_TYPE'] == 'X' || $arCatalog['CATALOG_TYPE'] == 'P') {
                        $iblockOffer = CCatalogSKU::GetInfoByProductIBlock($iblock['ID']);
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
        CEventLog::Add([
            'SEVERITY'      => 'SECURITY',
            'AUDIT_TYPE_ID' => $auditType,
            'MODULE_ID'     => self::$MODULE_ID,
            'ITEM_ID'       => $itemId,
            'DESCRIPTION'   => $description,
        ]);
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
     *
     * @throws \Throwable
     */
    public static function orderAgent()
    {
        if (COption::GetOptionString('main', 'agents_use_crontab', 'N') !== 'N') {
            define('NO_AGENT_CHECK', true);
        }

        try {
            $service = ManagerService::getInstance();
            $service->synchronizeManagers();

            RetailCrmHistory::customerHistory();
            RetailCrmHistory::orderHistory();
            self::uploadOrdersAgent();
        } catch (\Throwable $exception) {
            Logger::getInstance()->write(
                'Fail orderAgent:' . PHP_EOL .
                $exception->getMessage() . PHP_EOL .
                'File: ' . $exception->getFile() . PHP_EOL .
                'Line: ' . $exception->getLine() . PHP_EOL,
                'orderAgent'
            );
        }

        return 'RCrmActions::orderAgent();';
    }

    /**
     * removes all empty fields from arrays
     * working with nested arrs
     *
     * @param array $arr
     *
     * @return array
     */
    public static function clearArr(array $arr): array
    {
        /** @var Utils $utils */
        $utils = ServiceLocator::getOrCreate(Utils::class);

        return $utils->clearArray($arr);
    }

    /**
     *
     * @param array|bool|\SplFixedArray|string $str in SITE_CHARSET
     *
     * @return array|bool|\SplFixedArray|string $str in utf-8
     */
    public static function toJSON($str)
    {
        /** @var Utils $utils */
        $utils = ServiceLocator::getOrCreate(Utils::class);

        return $utils->toUTF8($str);
    }

    /**
     *
     * @param string|array|\SplFixedArray $str in utf-8
     *
     * @return array|bool|\SplFixedArray|string $str in SITE_CHARSET
     */
    public static function fromJSON($str)
    {
        if ($str === null) { 
            return ''; 
        }

        /** @var Utils $utils */
        $utils = ServiceLocator::getOrCreate(Utils::class);

        return $utils->fromUTF8($str);
    }

    /**
     * Extracts payment ID or client ID from payment externalId
     * Payment ID - pass nothing or 'id' as second argument
     * Client ID - pass 'client_id' as second argument
     *
     * @param $externalId
     * @param string $data
     * @return bool|string
     */
    public static function getFromPaymentExternalId($externalId, $data = 'id')
    {
        switch ($data) {
            case 'id':
                if (false === strpos($externalId, '_')) {
                    return $externalId;
                } else {
                    return substr($externalId, 0, strpos($externalId, '_'));
                }

                break;

            case 'client_id':
                if (false === strpos($externalId, '_')) {
                    return '';
                } else {
                    return substr($externalId, strpos($externalId, '_'), count($externalId));
                }

                break;
        }

        return '';
    }

    /**
     * Returns true if provided externalId in new format (id_clientId)
     *
     * @param $externalId
     * @return bool
     */
    public static function isNewExternalId($externalId)
    {
        return !(false === strpos($externalId, '_'));
    }

    /**
     * Generates payment external ID
     *
     * @param $id
     *
     * @return string
     */
    public static function generatePaymentExternalId($id)
    {
        return sprintf(
            '%s_%s',
            $id,
            COption::GetOptionString(self::$MODULE_ID, 'client_id', 0)
        );
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

    /**
     * @param string|null $fio
     *
     * @return array
     */
    public static function explodeFio(?string $fio): array
    {
        $result = [];
        $fio = preg_replace('|[\s]+|s', ' ', trim($fio));

        if (empty($fio)) {
            return $result;
        } else {
            $newFio = explode(' ', $fio, 3);
        }

        switch (count($newFio)) {
            default:
            case 0:
                $result['firstName'] = $fio;
                break;
            case 1:
                $result['firstName'] = $newFio[0];
                break;
            case 2:
                $result = [
                    'lastName'  => $newFio[0],
                    'firstName' => $newFio[1],
                ];
                break;
            case 3:
                $result = [
                    'lastName'   => $newFio[0],
                    'firstName'  => $newFio[1],
                    'patronymic' => $newFio[2],
                ];
                break;
        }

        return $result;
    }

    public static function customOrderPropList()
    {
        $typeMatched = [
            'STRING' => 'STRING',
            'NUMBER' => 'NUMERIC',
            'Y/N' => 'BOOLEAN',
            'DATE' => 'DATE'
        ];

        //Базовые свойства заказа и используемые свойства в функционале модуля
        $bannedCodeList = [
            'FIO',
            'EMAIL',
            'PHONE',
            'ZIP',
            'CITY',
            'LOCATION',
            'ADDRESS',
            'COMPANY',
            'COMPANY_ADR',
            'INN',
            'KPP',
            'CONTACT_PERSON',
            'FAX',
            'LP_BONUS_INFO',
            'LP_DISCOUNT_INFO',
            ''
        ];

        $listPersons = PersonType::getList([
            'select' => ['ID', 'NAME'],
            'filter' => ['ENTITY_REGISTRY_TYPE' => 'ORDER']
        ])->fetchAll();

        $persons = [];

        foreach ($listPersons as $person) {
            $persons[$person['ID']] = $person['NAME'];
        }

        $propsList = OrderPropsTable::getList([
            'select' => ['ID', 'CODE', 'NAME', 'PERSON_TYPE_ID', 'TYPE'],
            'filter' => [
                ['!=CODE' => $bannedCodeList],
                ['?TYPE' => 'STRING | NUMBER | Y/N | DATE'],
                ['MULTIPLE' => 'N'],
                ['ACTIVE' => 'Y']
            ]
        ])->fetchAll();

        $resultList = [];

        foreach ($propsList as $prop) {
            $type = $typeMatched[$prop['TYPE']] ?? $prop['TYPE'];
            $key = $prop['ID'] . '#' . $prop['CODE'];
            $resultList[$type . '_TYPE'][$key] = $prop['NAME'] . ' (' . $persons[$prop['PERSON_TYPE_ID']] . ')';
        }

        ksort($resultList);

        return $resultList;
    }

    public static function customUserFieldList()
    {
        $typeMatched = [
            'string' => 'STRING',
            'double' => 'NUMERIC',
            'boolean' => 'BOOLEAN',
            'date' => 'DATE',
            'integer' => 'INTEGER'
        ];

        $userFields = UserFieldTable::getList([
            'select' => ['ID', 'FIELD_NAME', 'USER_TYPE_ID'],
            'filter' => [
                ['ENTITY_ID' => 'USER'],
                ['?FIELD_NAME' => '~%INTARO%'],
                ['!=FIELD_NAME' => 'UF_SUBSCRIBE_USER_EMAIL'],
                ['!=USER_TYPE_ID' => 'datetime'],
                ['?USER_TYPE_ID' => 'string | date | integer | double | boolean'],
                ['MULTIPLE' => 'N'],
            ]
        ])->fetchAll();

        $resultList = [];

        foreach ($userFields as $userField) {
            $label = UserFieldLangTable::getList([
                'select' => ['EDIT_FORM_LABEL'],
                'filter' => [
                    ["USER_FIELD_ID" => $userField['ID']],
                    ['LANGUAGE_ID' => LANGUAGE_ID]
                ]
            ])->fetch();

            $type = $typeMatched[$userField['USER_TYPE_ID']] ?? $userField['USER_TYPE_ID'];
            $resultList[$type . '_TYPE'][$userField['FIELD_NAME']] = $label['EDIT_FORM_LABEL'];
        }

        ksort($resultList);

        return $resultList;
    }

    public static function getTypeUserField()
    {
        $userFields = UserFieldTable::getList([
            'select' => ['FIELD_NAME', 'USER_TYPE_ID'],
            'filter' => [
                ['ENTITY_ID' => 'USER'],
                ['?FIELD_NAME' => '~%INTARO%'],
                ['!=FIELD_NAME' => 'UF_SUBSCRIBE_USER_EMAIL'],
                ['?USER_TYPE_ID' => 'string | date | datetime | integer | double | boolean'],
                ['MULTIPLE' => 'N'],
            ]
        ])->fetchAll();

        $result = [];

        foreach ($userFields as $userField) {
            $result[$userField['FIELD_NAME']] = $userField['USER_TYPE_ID'];
        }

        return $result;
    }

    public static function convertCmsFieldToCrmValue($value, $type)
    {
        $result = $value;

        switch ($type) {
            case 'boolean':
                $result = $value === '1' ? 1 : 0;
                break;
            case 'Y/N':
                $result = $result === 'Y' ? 1 : 0;
                break;
            case 'STRING':
            case 'string':
                $result =  strlen($value) <= 500 ? $value : '';
                break;
            case 'datetime':
                $result = date('Y-m-d', strtotime($value));
                break;
        }

        return $result;
    }

    public static function convertCrmValueToCmsField($crmValue, $type)
    {
        $result = $crmValue;

        switch ($type) {
            case 'Y/N':
            case 'boolean':
                $result = $crmValue == 1 ? 'Y' : 'N';
                break;
            case 'DATE':
            case 'date':
                if (empty($crmValue)) {
                    return '';
                }

                try {
                    $result = date('d.m.Y', strtotime($crmValue));
                } catch (\Exception $exception) {
                    $result = '';
                }

                break;
            case 'STRING':
            case 'string':
            case 'text':
                $result = strlen($crmValue) <= 500 ? $crmValue : '';
                break;
        }

        return $result;
    }

    public static function sendConfiguration($api, $active = true)
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

        $configuration = [
            'clientId' => $clientId,
            'code' => $code,
            'integrationCode' => $integrationCode,
            'active' => $active,
            'name' => GetMessage('API_MODULE_NAME'),
            'logo' => $logo,
            'baseUrl' => $baseUrl,
            'accountUrl' => $accountUrl
        ];

        self::apiMethod($api, 'integrationModulesEdit', __METHOD__, $configuration);
    }

    public static function apiMethod($api, $methodApi, $method, $params, $site = null)
    {
        switch ($methodApi) {
            case 'ordersPaymentDelete':
            case 'ordersHistory':
            case 'customerHistory':
            case 'ordersFixExternalIds':
            case 'customersFixExternalIds':
            case 'customersCorporateContacts':
            case 'customersList':
            case 'customersCorporateList':
                return self::proxy($api, $methodApi, $method, [$params]);
            case 'orderGet':
                return self::proxy($api, 'ordersGet', $method, [$params, 'id', $site]);

            case 'ordersGet':
            case 'ordersEdit':
            case 'customersGet':
            case 'customersEdit':
            case 'customersCorporateGet':
                return self::proxy($api, $methodApi, $method, [$params, 'externalId', $site]);
            case 'customersCorporateGetById':
                return self::proxy($api, 'customersCorporateGet', $method, [$params, 'id', $site]);
            case 'customersGetById':
                return self::proxy($api, 'customersGet', $method, [$params, 'id', $site]);

            case 'paymentEditById':
                return self::proxy($api, 'ordersPaymentEdit', $method, [$params, 'id', $site]);

            case 'paymentEditByExternalId':
                return self::proxy($api, 'ordersPaymentEdit', $method, [$params, 'externalId', $site]);
            case 'customersCorporateEdit':
                return self::proxy($api, 'customersCorporateEdit', $method, [$params, 'externalId', $site]);
            case 'cartGet':
                return self::proxy($api, $methodApi, $method, [$params, $site, 'externalId']);
            case 'cartSet':
            case 'cartClear':
                return self::proxy($api, $methodApi, $method, [$params, $site]);
            default:
            return self::proxy($api, $methodApi, $method, [$params, $site]);
        }
    }

    private static function proxy($api, $methodApi, $method, $params) {
        $version = COption::GetOptionString(self::$MODULE_ID, self::$CRM_API_VERSION, 0);
        try {
            $result = call_user_func_array([$api, $methodApi], $params);

            if (!$result) {
                $err = new RuntimeException(
                    $methodApi . ': Got null instead of valid result!'
                );
                Logger::getInstance()->write(sprintf(
                    '%s%s%s',
                    $err->getMessage(),
                    PHP_EOL,
                    $err->getTraceAsString()
                ), 'apiErrors');

                return false;
            }

            if ($result->getStatusCode() !== 200 && $result->getStatusCode() !== 201) {
                if ($methodApi == 'ordersGet'
                    || $methodApi == 'customersGet'
                    || $methodApi == 'customersCorporateGet'
                ) {
                    Logger::getInstance()->write([
                        'api' => $version,
                        'methodApi' => $methodApi,
                        'errorMsg' => !empty($result['errorMsg']) ? $result['errorMsg'] : '',
                        'errors' => !empty($result['errors']) ? $result['errors'] : '',
                        'params' => $params
                    ], 'apiErrors');
                } elseif ($methodApi == 'customersUpload' || $methodApi == 'ordersUpload') {
                    Logger::getInstance()->write([
                        'api' => $version,
                        'methodApi' => $methodApi,
                        'errorMsg' => !empty($result['errorMsg']) ? $result['errorMsg'] : '',
                        'errors' => !empty($result['errors']) ? $result['errors'] : '',
                        'params' => $params
                    ], 'uploadApiErrors');
                } elseif ($methodApi == 'cartGet') {
                    Logger::getInstance()->write(
                        [
                            'api' => $version,
                            'methodApi' => $methodApi,
                            'errorMsg' => !empty($result['errorMsg']) ? $result['errorMsg'] : '',
                            'errors' => !empty($result['errors']) ? $result['errors'] : '',
                            'params' => $params,
                        ],
                        'apiErrors'
                    );
                } else {
                    self::eventLog(
                        __CLASS__ . '::' . $method,
                        'RetailCrm\ApiClient::' . $methodApi,
                        !empty($result['errorMsg']) ? $result['errorMsg'] : ''
                    );

                    Logger::getInstance()->write([
                        'api' => $version,
                        'methodApi' => $methodApi,
                        'errorMsg' => !empty($result['errorMsg']) ? $result['errorMsg'] : '',
                        'errors' => !empty($result['errors']) ? $result['errors'] : '',
                        'params' => $params,
                    ], 'apiErrors');
                }

                if (function_exists('retailCrmApiResult')) {
                    retailCrmApiResult($methodApi, false, $result->getStatusCode());
                }

                if ($result->getStatusCode() == 460) {
                    return true;
                }

                return false;
            }
        } catch (CurlException $e) {
            static::logException(
                $method,
                $methodApi,
                'CurlException',
                'CurlException',
                $e,
                $version,
                $params
            );

            return false;
        } catch (InvalidArgumentException $e) {
            static::logException(
                $method,
                $methodApi,
                'InvalidArgumentException',
                'ArgumentException',
                $e,
                $version,
                $params
            );

            return false;
        } catch (InvalidJsonException $e) {
            static::logException(
                $method,
                $methodApi,
                'InvalidJsonException',
                'ArgumentException',
                $e,
                $version,
                $params
            );
        }

        if (function_exists('retailCrmApiResult')) {
            retailCrmApiResult($methodApi, true, isset($result) ? $result->getStatusCode() : 0);
        }

        return isset($result) ? $result : false;
    }

    /**
     * Log exception into log file and event log
     *
     * @param string                       $method
     * @param string                       $methodApi
     * @param string                       $exceptionName
     * @param string                       $apiResultExceptionName
     * @param \Exception|\Error|\Throwable $exception
     * @param string                       $version
     * @param array                        $params
     */
    protected static function logException(
        $method,
        $methodApi,
        $exceptionName,
        $apiResultExceptionName,
        $exception,
        $version,
        $params
    ) {
        self::eventLog(
            __CLASS__ . '::' . $method, 'RetailCrm\ApiClient::' . $methodApi . '::' . $exceptionName,
            $exception->getCode() . ': ' . $exception->getMessage()
        );

        Logger::getInstance()->write([
            'api' => $version,
            'methodApi' => $methodApi,
            'errorMsg' => $exception->getMessage(),
            'errors' => $exception->getCode(),
            'params' => $params
        ], 'apiErrors');

        if (function_exists('retailCrmApiResult')) {
            retailCrmApiResult($methodApi, false, $apiResultExceptionName);
        }
    }
}
