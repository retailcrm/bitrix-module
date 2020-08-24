<?php
/**
 * PHP version 7.1
 *
 * @category Integration
 * @package  Intaro\RetailCrm\Component
 * @author   retailCRM <integration@retailcrm.ru>
 * @license  MIT
 * @link     http://retailcrm.ru
 * @see      http://retailcrm.ru/docs
 */
namespace Intaro\RetailCrm\Component;

use Bitrix\Currency\CurrencyManager;
use Bitrix\Main\ArgumentNullException;
use Bitrix\Main\ArgumentOutOfRangeException;
use Bitrix\Main\Config\Option;
use Intaro\RetailCrm\Service\Utils;

/**
 * Class ConfigProvider
 *
 * @package Intaro\RetailCrm\Component
 */
class ConfigProvider
{
    /** @var bool|null|string */
    protected static $apiUrl;

    /** @var bool|null|string */
    protected static $apiKey;

    /** @var bool|null|string */
    protected static $catalogBasePrice;

    /** @var bool|null|string */
    protected static $currency;

    /** @var bool|null|string */
    protected static $orderDimensions;

    /** @var bool|null|string */
    protected static $corporateClientName;

    /** @var bool|null|string */
    protected static $corporateClientAddress;

    /** @var bool|null|string */
    protected static $corporateClient;

    /** @var bool|null|string $shipmentDeducted */
    protected static $shipmentDeducted;

    /** @var array $sitesList */
    protected static $sitesList;

    /** @var array $sitesListCorporate */
    protected static $sitesListCorporate;

    /** @var bool|null|string $orderNumbers */
    protected static $orderNumbers;

    /** @var array $orderTypes */
    protected static $orderTypes;

    /** @var array $deliveryTypes */
    protected static $deliveryTypes;

    /** @var array $paymentTypes */
    protected static $paymentTypes;

    /** @var array $paymentStatuses */
    protected static $paymentStatuses;

    /** @var array $payment */
    protected static $payment;

    /** @var array $orderProps */
    protected static $orderProps;

    /** @var array $legalDetails */
    protected static $legalDetails;

    /** @var array $contragentTypes */
    protected static $contragentTypes;

    /** @var array $cancellableOrderPaymentStatuses */
    protected static $cancellableOrderPaymentStatuses;

    /** @var array $customFields */
    protected static $customFields;

    /** @var array $infoblocksInventories */
    protected static $infoblocksInventories;

    /** @var array $stores */
    protected static $stores;

    /** @var array $shops */
    protected static $shops;

    /** @var array $integrationDeliveriesMapping */
    protected static $integrationDeliveriesMapping;

    /** @var bool|null|string $loyaltyProgramStatus */
    protected static $loyaltyProgramStatus;

    /**
     * @return bool|string|null
     */
    public static function getApiUrl()
    {
        if (self::isEmptyNotZero(static::$apiUrl)) {
            static::$apiUrl = static::getOption(Constants::CRM_API_HOST_OPTION);
        }

        return static::$apiUrl;
    }

    /**
     * @return bool|string|null
     */
    public static function getApiKey()
    {
        if (self::isEmptyNotZero(static::$apiKey)) {
            static::$apiKey = static::getOption(Constants::CRM_API_KEY_OPTION);
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
            static::$corporateClientName = static::getUnserializedOption(Constants::CRM_CORP_NAME);
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
            static::$corporateClientAddress = static::getUnserializedOption(Constants::CRM_CORP_ADDRESS);
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
            static::$corporateClient = static::getOption(Constants::CRM_CC);
        }

        return static::$corporateClient;
    }

    /**
     * Returns true if corporate clients are enabled
     *
     * @return bool
     */
    public static function isCorporateClientEnabled(): bool
    {
        return self::getCorporateClientStatus() === 'Y';
    }

    /**
     * getSitesList
     *
     * @return array
     */
    public static function getSitesList()
    {
        if (self::isEmptyNotZero(static::$sitesList)) {
            static::$sitesList = static::getUnserializedOption(Constants::CRM_SITES_LIST);
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
                Constants::CRM_SITES_LIST_CORPORATE
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
            static::$orderTypes = static::getUnserializedOption(Constants::CRM_ORDER_TYPES_ARR);
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
        if (self::isEmptyNotZero(static::$deliveryTypes)) {
            static::$deliveryTypes = static::getUnserializedOption(Constants::CRM_DELIVERY_TYPES_ARR);
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
        if (self::isEmptyNotZero(static::$paymentTypes)) {
            static::$paymentTypes = static::getUnserializedOption(Constants::CRM_PAYMENT_TYPES);
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
        if (self::isEmptyNotZero(static::$paymentStatuses)) {
            static::$paymentStatuses = static::getUnserializedOption(Constants::CRM_PAYMENT_STATUSES);
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
        if (self::isEmptyNotZero(static::$payment)) {
            static::$payment = static::getUnserializedOption(Constants::CRM_PAYMENT);
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
            static::$orderProps = static::getUnserializedOption(Constants::CRM_ORDER_PROPS);
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
            static::$legalDetails = static::getUnserializedOption(Constants::CRM_LEGAL_DETAILS);
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
            static::$contragentTypes = static::getUnserializedOption(Constants::CRM_CONTRAGENT_TYPE);
        }

        return static::$contragentTypes;
    }

    /**
     * Returns contragent type for provided person type (PERSON_TYPE_ID in the Bitrix order).
     * Returns null if nothing was found.
     *
     * @param string $personTypeId
     *
     * @return string|null
     */
    public static function getContragentTypeForPersonType(string $personTypeId): ?string
    {
        $personTypes = static::getContragentTypes();

        if (!empty($personTypes[$personTypeId])) {
            return $personTypes[$personTypeId];
        }

        return null;
    }

    /**
     * getCustomFields
     *
     * @return array
     */
    public static function getCustomFields()
    {
        if (self::isEmptyNotZero(static::$customFields)) {
            static::$customFields = static::getUnserializedOption(Constants::CRM_CUSTOM_FIELDS);
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
                Constants::CRM_CANCEL_ORDER
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
        return static::getOption(Constants::CRM_ORDER_LAST_ID);
    }

    /**
<<<<<<< HEAD
     * getSendPaymentAmount
     *
     * @return bool|string|null
     */
    public static function getSendPaymentAmount()
    {
        return static::getOption(Constants::SEND_PAYMENT_AMOUNT);
    }

    /**
     * setSendPaymentAmount
     *
     * @param string $value
     *
     * @throws \Bitrix\Main\ArgumentOutOfRangeException
     */
    public static function setSendPaymentAmount($value)
    {
        static::setOption(Constants::SEND_PAYMENT_AMOUNT, $value);
    }

    /**
     * Returns true if payment amount should be sent from CMS to RetailCRM.
     *
     * @return bool|string|null
     */
    public static function shouldSendPaymentAmount()
    {
        return static::getSendPaymentAmount() === 'Y';
    }

    /**
     * Return integration_delivery option
     *
     * @return mixed
     */
    public static function getCrmIntegrationDelivery()
    {
        return static::getUnserializedOption(Constants::CRM_INTEGRATION_DELIVERY);
    }

    /**
     * setLastOrderId
     *
     * @param $id
     *
     * @throws \Bitrix\Main\ArgumentOutOfRangeException
=======
     * setLastOrderId
     *
     * @param $id
>>>>>>> ddb37d1 (New module structure (refactoring))
     */
    public static function setLastOrderId($id): void
    {
        static::setOption(Constants::CRM_ORDER_LAST_ID, $id);
    }

    /**
     * getFailedOrdersIds
     *
     * @return array
     */
    public static function getFailedOrdersIds()
    {
        return static::getUnserializedOption(Constants::CRM_ORDER_FAILED_IDS);
    }

    /**
     * setFailedOrdersIds
     *
     * @param $ids
     */
    public static function setFailedOrdersIds($ids): void
    {
        static::setOption(Constants::CRM_ORDER_FAILED_IDS, serialize($ids));
    }

    /**
     * getOrderNumbers
     *
     * @return bool|string|null
     */
    public static function getOrderNumbers()
    {
        if (self::isEmptyNotZero(self::$orderNumbers)) {
            self::$orderNumbers = static::getOption(Constants::CRM_ORDER_NUMBERS);
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
        return static::getOption(Constants::CRM_ORDER_HISTORY_DATE);
    }

    /**
     * Returns customers history since ID
     *
     * @return int
     */
    public static function getCustomersHistorySinceId(): int
    {
        return (int) static::getOption(Constants::CRM_CUSTOMERS_HISTORY_SINCE_ID);
    }

    /**
     * Sets new customers history since ID
     *
     * @param int $sinceId
     */
    public static function setCustomersHistorySinceId($sinceId): void
    {
        static::setOption(Constants::CRM_CUSTOMERS_HISTORY_SINCE_ID, $sinceId);
    }

    /**
     * getCatalogBasePrice
     *
     * @return bool|string|null
     */
    public static function getCatalogBasePrice()
    {
        if (self::isEmptyNotZero(static::$catalogBasePrice)) {
            static::$catalogBasePrice = static::getOption(Constants::CRM_CATALOG_BASE_PRICE);
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
            static::$orderDimensions = static::getOption(Constants::CRM_ORDER_DIMENSIONS, 'N');
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
            static::$currency = static::getOption(Constants::CRM_CURRENCY);
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
        return self::getCurrency() ?: CurrencyManager::getBaseCurrency();
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
                Constants::CRM_IBLOCKS_INVENTORIES
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
            static::$stores = static::getUnserializedOption(Constants::CRM_STORES);
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
            static::$shops = static::getUnserializedOption(Constants::CRM_SHOPS);
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
            static::$shipmentDeducted = static::getOption(Constants::CRM_SHIPMENT_DEDUCTED);
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
        return static::getExternalOption("main", "new_user_phone_required") === 'Y';
    }

    /**
     * Returns integration delivery mapping
     *
     * @return array
     */
    public static function getIntegrationDeliveriesMapping(): array
    {
        if (empty(self::$integrationDeliveriesMapping)) {
            self::$integrationDeliveriesMapping =
                (array) static::getUnserializedOption(Constants::CRM_INTEGRATION_DELIVERY);
        }

        return self::$integrationDeliveriesMapping;
    }

    /**
     * Wraps Bitrix \COption::GetOptionString(...)
     *
     * @param string $option
     * @param int|string $def
     *
     * @return string|null
     */
    protected static function getOption($option, $def = 0): ?string
    {
        return static::getExternalOption(Constants::MODULE_ID, $option, $def);
    }

    /**
     * Returns option from provided module
     *
     * @param string $moduleId
     * @param string $option
     * @param int    $def
     *
     * @return string|null
     */
    protected static function getExternalOption(string $moduleId, string $option, $def = 0): ?string
    {
        try {
            return Option::get($moduleId, $option, $def);
        } catch (ArgumentNullException | ArgumentOutOfRangeException $e) {
            return null;
        }
    }

    /**
     * setOption
     *
     * @param        $name
     * @param string $value
     * @param string $site
     *
     * @throws \Bitrix\Main\ArgumentOutOfRangeException
     */
    protected static function setOption($name, $value = "", $site = ""): void
    {
        Option::set(
            Constants::MODULE_ID,
            $name,
            $value,
            $site
        );
    }

    /**
     * Wraps Bitrix unserialize(\COption::GetOptionString(...))
     *
     * @param string  $option
     * @param int|string $def
     *
     * @return mixed
     */
    protected static function getUnserializedOption($option, $def = 0)
    {
        return unserialize(static::getOption($option, $def), null);
    }

    /**
     * Returns true if value is empty and not zero (0 - digit)
     *
     * @param mixed $value
     *
     * @return bool
     */
    protected static function isEmptyNotZero($value): bool
    {
        return empty($value) && $value !== 0;
    }

    /**
     * isOnlineConsultantEnabled
     *
     * @return bool
     */
    public static function isOnlineConsultantEnabled()
    {
        return static::getOption(Constants::CRM_ONLINE_CONSULTANT) === 'Y';
    }

    /**
     * getOnlineConsultantScript
     *
     * @return string
     */
    public static function getOnlineConsultantScript()
    {
        return trim(static::getOption(Constants::CRM_ONLINE_CONSULTANT_SCRIPT, ""));
    }

    /**
     * setOnlineConsultant
     *
     * @param string $value
     */
    public static function setOnlineConsultant($value)
    {
        static::setOption(Constants::CRM_ONLINE_CONSULTANT, $value);
    }

    /**
     * setOnlineConsultantScript
     *
     * @param string $value
     */
    public static function setOnlineConsultantScript($value)
    {
        static::setOption(Constants::CRM_ONLINE_CONSULTANT_SCRIPT, $value);
    }

    /**
     * setOrderTypes
     *
     * @param array $orderTypesArr
     */
    public static function setOrderTypes($orderTypesArr)
    {
        static::setOption(Constants::CRM_ORDER_TYPES_ARR, serialize(self::getUtils()->clearArray($orderTypesArr)));
    }

    /**
     * setDeliveryTypes
     *
     * @param array $deliveryTypesArr
     */
    public static function setDeliveryTypes($deliveryTypesArr)
    {
        static::setOption(Constants::CRM_DELIVERY_TYPES_ARR, serialize(self::getUtils()->clearArray($deliveryTypesArr)));
    }

    /**
     * setPaymentTypes
     *
     * @param array $paymentTypesArr
     */
    public static function setPaymentTypes($paymentTypesArr)
    {
        static::setOption(Constants::CRM_PAYMENT_TYPES, serialize(self::getUtils()->clearArray($paymentTypesArr)));
    }

    /**
     * getPaymentStatuses
     *
     * @param array $paymentStatusesArr
     */
    public static function setPaymentStatuses($paymentStatusesArr)
    {
        static::setOption(Constants::CRM_PAYMENT_STATUSES, serialize(self::getUtils()->clearArray($paymentStatusesArr)));
    }

    /**
     * setContragentTypes
     *
     * @param array $contragentTypeArr
     */
    public static function setContragentTypes($contragentTypeArr)
    {
        static::setOption(Constants::CRM_CONTRAGENT_TYPE, serialize(self::getUtils()->clearArray($contragentTypeArr)));
    }

    /**
     * @return \Intaro\RetailCrm\Service\Utils
     */
    public static function getUtils(): Utils
    {
        return ServiceLocator::get(Utils::class);
    }

    /**
     * @return bool|string|null
     */
    public static function getLoyaltyProgramStatus()
    {
        return static::getOption(Constants::LOYALTY_PROGRAM_TOGGLE);
    }

    /**
     * @param bool|string|null $loyaltyProgramStatus
     * @throws \Bitrix\Main\ArgumentOutOfRangeException
     */
    public static function setLoyaltyProgramStatus($loyaltyProgramStatus): void
    {
        static::setOption(Constants::LOYALTY_PROGRAM_TOGGLE, $loyaltyProgramStatus);
    }
}
