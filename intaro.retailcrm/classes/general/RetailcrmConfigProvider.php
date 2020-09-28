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
    private static $corporateClientName;

    /** @var bool|null|string */
    private static $corporateClientAddress;

    /** @var bool|null|string */
    private static $corporateClient;

    /** @var bool|null|string $shipmentDeducted */
    private static $shipmentDeducted;

    /** @var array $sitesList */
    private static $sitesList;

    /** @var array $sitesListCorporate */
    private static $sitesListCorporate;

    /** @var bool|null|string $orderNumbers */
    private static $orderNumbers;

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

    /** @var array $cancellableOrderPaymentStatuses */
    private static $cancellableOrderPaymentStatuses;

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
        if (self::isEmptyNotZero(static::$apiUrl)) {
            static::$apiUrl = static::getOption(RetailcrmConstants::CRM_API_HOST_OPTION);
        }

        return static::$apiUrl;
    }

    /**
     * @return bool|string|null
     */
    public static function getApiKey()
    {
        if (self::isEmptyNotZero(static::$apiKey)) {
            static::$apiKey = static::getOption(RetailcrmConstants::CRM_API_KEY_OPTION);
        }

        return static::$apiKey;
    }

    /**
     * getCorporateClientName
     *
     * @return bool|string|null
     */
    public static function getCorporateClientName()
    {
        if (self::isEmptyNotZero(static::$corporateClientName)) {
            static::$corporateClientName = static::getUnserializedOption(RetailcrmConstants::CRM_CORP_NAME);
        }

        return static::$corporateClientName;
    }

    /**
     * getCorporateClientAddress
     *
     * @return bool|string|null
     */
    public static function getCorporateClientAddress()
    {
        if (self::isEmptyNotZero(static::$corporateClientAddress)) {
            static::$corporateClientAddress = static::getUnserializedOption(RetailcrmConstants::CRM_CORP_ADDRESS);
        }

        return static::$corporateClientAddress;
    }

    /**
     * getCorporateClient
     *
     * @return bool|string|null
     */
    public static function getCorporateClientStatus()
    {
        if (self::isEmptyNotZero(static::$corporateClient)) {
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
        if (self::isEmptyNotZero(static::$sitesList)) {
            static::$sitesList = static::getUnserializedOption(RetailcrmConstants::CRM_SITES_LIST);
        }

        return static::$sitesList;
    }

    /**
     * getSitesListCorporate
     *
     * @return array
     */
    public static function getSitesListCorporate()
    {
        if (self::isEmptyNotZero(static::$sitesListCorporate)) {
            static::$sitesListCorporate = static::getUnserializedOption(
                RetailcrmConstants::CRM_SITES_LIST_CORPORATE
            );
        }

        return static::$sitesListCorporate;
    }

    /**
     * getOrderTypes
     *
     * @return array
     */
    public static function getOrderTypes()
    {
        if (self::isEmptyNotZero(static::$orderTypes)) {
            static::$orderTypes = static::getUnserializedOption(RetailcrmConstants::CRM_ORDER_TYPES_ARR);
        }

        return static::$orderTypes;
    }

    /**
     * setOrderTypes
     *
     * @param array $orderTypesArr
     */
    public static function setOrderTypes($orderTypesArr)
    {
        static::setOption(RetailcrmConstants::CRM_ORDER_TYPES_ARR, serialize(RCrmActions::clearArr($orderTypesArr)));
    }

    /**
     * getDeliveryTypes
     *
     * @return array
     */
    public static function getDeliveryTypes()
    {
        if (self::isEmptyNotZero(static::$deliveryTypes)) {
            static::$deliveryTypes = static::getUnserializedOption(RetailcrmConstants::CRM_DELIVERY_TYPES_ARR);
        }

        return static::$deliveryTypes;
    }

    /**
     * setDeliveryTypes
     *
     * @param array $deliveryTypesArr
     */
    public static function setDeliveryTypes($deliveryTypesArr)
    {
        static::setOption(RetailcrmConstants::CRM_DELIVERY_TYPES_ARR, serialize(RCrmActions::clearArr($deliveryTypesArr)));
    }

    /**
     * getPaymentTypes
     *
     * @return array
     */
    public static function getPaymentTypes()
    {
        if (self::isEmptyNotZero(static::$paymentTypes)) {
            static::$paymentTypes = static::getUnserializedOption(RetailcrmConstants::CRM_PAYMENT_TYPES);
        }

        return static::$paymentTypes;
    }

    /**
     * setPaymentTypes
     *
     * @param array $paymentTypesArr
     */
    public static function setPaymentTypes($paymentTypesArr)
    {
        static::setOption(RetailcrmConstants::CRM_PAYMENT_TYPES, serialize(RCrmActions::clearArr($paymentTypesArr)));
    }

    /**
     * getPaymentStatuses
     *
     * @return array
     */
    public static function getPaymentStatuses()
    {
        if (self::isEmptyNotZero(static::$paymentStatuses)) {
            static::$paymentStatuses = static::getUnserializedOption(RetailcrmConstants::CRM_PAYMENT_STATUSES);
        }

        return static::$paymentStatuses;
    }

    /**
     * getPaymentStatuses
     *
     * @param array $paymentStatusesArr
     */
    public static function setPaymentStatuses($paymentStatusesArr)
    {
        static::setOption(RetailcrmConstants::CRM_PAYMENT_STATUSES, serialize(RCrmActions::clearArr($paymentStatusesArr)));
    }

    /**
     * getPayment
     *
     * @return array
     */
    public static function getPayment()
    {
        if (self::isEmptyNotZero(static::$payment)) {
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
        if (self::isEmptyNotZero(static::$orderProps)) {
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
        if (self::isEmptyNotZero(static::$legalDetails)) {
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
        if (self::isEmptyNotZero(static::$contragentTypes)) {
            static::$contragentTypes = static::getUnserializedOption(RetailcrmConstants::CRM_CONTRAGENT_TYPE);
        }

        return static::$contragentTypes;
    }

    /**
     * setContragentTypes
     *
     * @param array $contragentTypeArr
     */
    public static function setContragentTypes($contragentTypeArr)
    {
        static::setOption(RetailcrmConstants::CRM_CONTRAGENT_TYPE, serialize(RCrmActions::clearArr($contragentTypeArr)));
    }

    /**
     * getCustomFields
     *
     * @return array
     */
    public static function getCustomFields()
    {
        if (self::isEmptyNotZero(static::$customFields)) {
            static::$customFields = static::getUnserializedOption(RetailcrmConstants::CRM_CUSTOM_FIELDS);
        }

        return static::$customFields;
    }

    /**
     * getCancellableOrderPaymentStatuses
     *
     * @return array
     */
    public static function getCancellableOrderPaymentStatuses()
    {
        if (self::isEmptyNotZero(static::$cancellableOrderPaymentStatuses)) {
            static::$cancellableOrderPaymentStatuses = static::getUnserializedOption(
                RetailcrmConstants::CRM_CANCEL_ORDER
            );
        }

        return static::$cancellableOrderPaymentStatuses;
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
     * getSendPaymentAmount
     *
     * @return bool|string|null
     */
    public static function getSendPaymentAmount()
    {
        return static::getOption(RetailcrmConstants::SEND_PAYMENT_AMOUNT);
    }

    /**
     * setSendPaymentAmount
     *
     * @param string $value
     */
    public static function setSendPaymentAmount($value)
    {
        static::setOption(RetailcrmConstants::SEND_PAYMENT_AMOUNT, $value);
    }

    /**
     * Returns true if payment amount should be sent from CMS to retailCRM.
     *
     * @return bool|string|null
     */
    public static function shouldSendPaymentAmount()
    {
        return static::getSendPaymentAmount() === 'Y';
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
     * @return bool|string|null
     */
    public static function getOrderNumbers()
    {
        if (self::isEmptyNotZero(self::$orderNumbers)) {
            self::$orderNumbers = static::getOption(RetailcrmConstants::CRM_ORDER_NUMBERS);
        }

        return self::$orderNumbers;
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
     * Returns customers history since ID
     *
     * @return int
     */
    public static function getCustomersHistorySinceId()
    {
        return (int) static::getOption(RetailcrmConstants::CRM_CUSTOMERS_HISTORY_SINCE_ID);
    }

    /**
     * Sets new customers history since ID
     *
     * @param int $sinceId
     */
    public static function setCustomersHistorySinceId($sinceId)
    {
        static::setOption(RetailcrmConstants::CRM_CUSTOMERS_HISTORY_SINCE_ID, $sinceId);
    }

    /**
     * getCatalogBasePrice
     *
     * @return bool|string|null
     */
    public static function getCatalogBasePrice()
    {
        if (self::isEmptyNotZero(static::$catalogBasePrice)) {
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
        if (self::isEmptyNotZero(static::$orderDimensions)) {
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
        if (self::isEmptyNotZero(static::$currency)) {
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
        if (self::isEmptyNotZero(static::$infoblocksInventories)) {
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
        if (self::isEmptyNotZero(static::$stores)) {
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
        if (self::isEmptyNotZero(static::$shops)) {
            static::$shops = static::getUnserializedOption(RetailcrmConstants::CRM_SHOPS);
        }

        return static::$shops;
    }
  
    /**
     * getShipmentDeducted
     *
     * @return bool|string|null
     */
    public static function getShipmentDeducted()
    {
        if (self::isEmptyNotZero(static::$shipmentDeducted)) {
            static::$shipmentDeducted = static::getOption(RetailcrmConstants::CRM_SHIPMENT_DEDUCTED);
        }

        return static::$shipmentDeducted;
     }
  
    /**
     * isPhoneRequired
     *
     * @return bool|string|null
     */
    public static function isPhoneRequired()
    {
        return COption::GetOptionString("main", "new_user_phone_required") === 'Y';
    }
    
    /**
     * Return integration_delivery option
     *
     * @return mixed
     */
    public static function getCrmIntegrationDelivery()
    {
        return static::getUnserializedOption(RetailcrmConstants::CRM_INTEGRATION_DELIVERY);
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

    /**
     * Returns true if value is empty and not zero (0 - digit)
     *
     * @param mixed $value
     *
     * @return bool
     */
    private static function isEmptyNotZero($value)
    {
        return empty($value) && $value !== 0;
    }
}
