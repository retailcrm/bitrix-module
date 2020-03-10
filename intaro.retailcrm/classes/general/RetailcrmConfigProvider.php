<?php

/**
 * PHP version 5.3
 *
 * RetailcrmConfigProvider class
 *
 * @category RetailCrm
 * @package  RetailCrm
 * @author   RetailCrm <integration@retailcrm.ru>
 * @license  https://opensource.org/licenses/MIT MIT License
 * @link     http://www.retailcrm.ru/docs/Developers/ApiVersion4
 */

IncludeModuleLangFile(__FILE__);

/**
 * PHP version 5.3
 *
 * RetailcrmConfigProvider class
 *
 * @category RetailCrm
 * @package  RetailCrm
 * @author   RetailCrm <integration@retailcrm.ru>
 * @license  https://opensource.org/licenses/MIT MIT License
 * @link     http://www.retailcrm.ru/docs/Developers/ApiVersion4
 */
class RetailcrmConfigProvider
{
    /** @var bool|null|string */
    private static $apiUrl;

    /** @var bool|null|string */
    private static $apiKey;

    /** @var bool|null|string */
    private static $catalogBasePrice;

    /** @var bool|null|string */
    private static $currency;

    /** @var bool|null|string */
    private static $orderDimensions;

    /** @var bool|null|string */
    private static $corporateClient;

    /** @var array $sitesList */
    private static $sitesList;

    /** @var array $orderTypes */
    private static $orderTypes;

    /** @var array $deliveryTypes */
    private static $deliveryTypes;

    /** @var array $paymentTypes */
    private static $paymentTypes;

    /** @var array $paymentStatuses */
    private static $paymentStatuses;

    /** @var array $payment */
    private static $payment;

    /** @var array $orderProps */
    private static $orderProps;

    /** @var array $legalDetails */
    private static $legalDetails;

    /** @var array $contragentTypes */
    private static $contragentTypes;

    /** @var array $customFields */
    private static $customFields;

    /** @var array $infoblocksInventories */
    private static $infoblocksInventories;

    /** @var array $stores */
    private static $stores;

    /** @var array $shops */
    private static $shops;

    /**
     * @return bool|string|null
     */
    public static function getApiUrl()
    {
        if (empty(static::$apiUrl)) {
            static::$apiUrl = static::getOption(RetailcrmConstants::CRM_API_HOST_OPTION);
        }

        return static::$apiUrl;
    }

    /**
     * @return bool|string|null
     */
    public static function getApiKey()
    {
        if (empty(static::$apiKey)) {
            static::$apiKey = static::getOption(RetailcrmConstants::CRM_API_KEY_OPTION);
        }

        return static::$apiKey;
    }

    /**
     * getCorporateClient
     *
     * @return bool|string|null
     */
    public static function getCorporateClientStatus()
    {
        if (empty(static::$corporateClient)) {
            static::$corporateClient = static::getOption(RetailcrmConstants::CRM_CC);
        }

        return static::$corporateClient;
    }

    /**
     * getSitesList
     *
     * @return array
     */
    public static function getSitesList()
    {
        if (empty(static::$sitesList)) {
            static::$sitesList = static::getUnserializedOption(RetailcrmConstants::CRM_SITES_LIST);
        }

        return static::$sitesList;
    }

    /**
     * getOrderTypes
     *
     * @return array
     */
    public static function getOrderTypes()
    {
        if (empty(static::$orderTypes)) {
            static::$orderTypes = static::getUnserializedOption(RetailcrmConstants::CRM_ORDER_TYPES_ARR);
        }

        return static::$orderTypes;
    }

    /**
     * getDeliveryTypes
     *
     * @return array
     */
    public static function getDeliveryTypes()
    {
        if (empty(static::$deliveryTypes)) {
            static::$deliveryTypes = static::getUnserializedOption(RetailcrmConstants::CRM_DELIVERY_TYPES_ARR);
        }

        return static::$deliveryTypes;
    }

    /**
     * getPaymentTypes
     *
     * @return array
     */
    public static function getPaymentTypes()
    {
        if (empty(static::$paymentTypes)) {
            static::$paymentTypes = static::getUnserializedOption(RetailcrmConstants::CRM_PAYMENT_TYPES);
        }

        return static::$paymentTypes;
    }

    /**
     * getPaymentStatuses
     *
     * @return array
     */
    public static function getPaymentStatuses()
    {
        if (empty(static::$paymentStatuses)) {
            static::$paymentStatuses = static::getUnserializedOption(RetailcrmConstants::CRM_PAYMENT_STATUSES);
        }

        return static::$paymentStatuses;
    }

    /**
     * getPayment
     *
     * @return array
     */
    public static function getPayment()
    {
        if (empty(static::$payment)) {
            static::$payment = static::getUnserializedOption(RetailcrmConstants::CRM_PAYMENT);
        }

        return static::$payment;
    }

    /**
     * getOrderProps
     *
     * @return array
     */
    public static function getOrderProps()
    {
        if (empty(static::$orderProps)) {
            static::$orderProps = static::getUnserializedOption(RetailcrmConstants::CRM_ORDER_PROPS);
        }

        return static::$orderProps;
    }

    /**
     * getLegalDetails
     *
     * @return array
     */
    public static function getLegalDetails()
    {
        if (empty(static::$legalDetails)) {
            static::$legalDetails = static::getUnserializedOption(RetailcrmConstants::CRM_LEGAL_DETAILS);
        }

        return static::$legalDetails;
    }

    /**
     * getContragentTypes
     *
     * @return array
     */
    public static function getContragentTypes()
    {
        if (empty(static::$contragentTypes)) {
            static::$contragentTypes = static::getUnserializedOption(RetailcrmConstants::CRM_CONTRAGENT_TYPE);
        }

        return static::$contragentTypes;
    }

    /**
     * getCustomFields
     *
     * @return array
     */
    public static function getCustomFields()
    {
        if (empty(static::$customFields)) {
            static::$customFields = static::getUnserializedOption(RetailcrmConstants::CRM_CUSTOM_FIELDS);
        }

        return static::$customFields;
    }

    /**
     * getLastOrderId
     *
     * @return bool|string|null
     */
    public static function getLastOrderId()
    {
        return static::getOption(RetailcrmConstants::CRM_ORDER_LAST_ID);
    }

    /**
     * setLastOrderId
     *
     * @param $id
     */
    public static function setLastOrderId($id)
    {
        static::setOption(RetailcrmConstants::CRM_ORDER_LAST_ID, $id);
    }

    /**
     * getFailedOrdersIds
     *
     * @return array
     */
    public static function getFailedOrdersIds()
    {
        return static::getUnserializedOption(RetailcrmConstants::CRM_ORDER_FAILED_IDS);
    }

    /**
     * setFailedOrdersIds
     *
     * @param $ids
     */
    public static function setFailedOrdersIds($ids)
    {
        static::setOption(RetailcrmConstants::CRM_ORDER_FAILED_IDS, serialize($ids));
    }

    /**
     * getOrderNumbers
     *
     * @return array
     */
    public static function getOrderNumbers()
    {
        return static::getUnserializedOption(RetailcrmConstants::CRM_ORDER_NUMBERS);
    }

    /**
     * getOrderHistoryDate
     *
     * @return bool|string|null
     */
    public static function getOrderHistoryDate()
    {
        return static::getOption(RetailcrmConstants::CRM_ORDER_HISTORY_DATE);
    }

    /**
     * getCatalogBasePrice
     *
     * @return bool|string|null
     */
    public static function getCatalogBasePrice()
    {
        if (empty(static::$catalogBasePrice)) {
            static::$catalogBasePrice = static::getOption(RetailcrmConstants::CRM_CATALOG_BASE_PRICE);
        }

        return static::$catalogBasePrice;
    }

    /**
     * getOrderDimensions
     *
     * @return bool|string|null
     */
    public static function getOrderDimensions()
    {
        if (empty(static::$orderDimensions)) {
            static::$orderDimensions = static::getOption(RetailcrmConstants::CRM_ORDER_DIMENSIONS, 'N');
        }

        return static::$orderDimensions;
    }

    /**
     * getCurrency
     *
     * @return bool|string|null
     */
    public static function getCurrency()
    {
        if (empty(static::$currency)) {
            static::$currency = static::getOption(RetailcrmConstants::CRM_CURRENCY);
        }

        return static::$currency;
    }

    /**
     * Returns currency from settings. If it's not set - returns Bitrix base currency.
     *
     * @return bool|string|null
     */
    public static function getCurrencyOrDefault()
    {
        return self::getCurrency() ? self::getCurrency() : \Bitrix\Currency\CurrencyManager::getBaseCurrency();
    }

    /**
     * getInfoblocksInventories
     *
     * @return array
     */
    public static function getInfoblocksInventories()
    {
        if (empty(static::$infoblocksInventories)) {
            static::$infoblocksInventories = static::getUnserializedOption(
                RetailcrmConstants::CRM_IBLOCKS_INVENTORIES
            );
        }

        return static::$infoblocksInventories;
    }

    /**
     * getStores
     *
     * @return array
     */
    public static function getStores()
    {
        if (empty(static::$stores)) {
            static::$stores = static::getUnserializedOption(RetailcrmConstants::CRM_STORES);
        }

        return static::$stores;
    }

    /**
     * getShops
     *
     * @return array
     */
    public static function getShops()
    {
        if (empty(static::$shops)) {
            static::$shops = static::getUnserializedOption(RetailcrmConstants::CRM_SHOPS);
        }

        return static::$shops;
    }

    /**
     * Wraps Bitrix COption::GetOptionString(...)
     *
     * @param string $option
     * @param int|string $def
     *
     * @return bool|string|null
     */
    private static function getOption($option, $def = 0)
    {
        return COption::GetOptionString(
            RetailcrmConstants::MODULE_ID,
            $option,
            $def
        );
    }

    /**
     * setOption
     *
     * @param        $name
     * @param string $value
     * @param bool   $desc
     * @param string $site
     */
    private static function setOption($name, $value = "", $desc = false, $site = "")
    {
        COption::SetOptionString(
            RetailcrmConstants::MODULE_ID,
            $name,
            $value,
            $desc,
            $site
        );
    }

    /**
     * Wraps Bitrix unserialize(COption::GetOptionString(...))
     *
     * @param string  $option
     * @param int|string $def
     *
     * @return mixed
     */
    private static function getUnserializedOption($option, $def = 0)
    {
        return unserialize(static::getOption($option, $def));
    }
}
