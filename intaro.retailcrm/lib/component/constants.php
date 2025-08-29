<?php

/**
 * @category Integration
 * @package  Intaro\RetailCrm\Component
 * @author   RetailCRM <integration@retailcrm.ru>
 * @license  MIT
 * @link     http://retailcrm.ru
 * @see      http://retailcrm.ru/docs
 */

namespace Intaro\RetailCrm\Component;

/**
 * Class Constants
 *
 * @package Intaro\RetailCrm\Component
 */
class Constants
{
    public const MODULE_VERSION = '6.7.4';
    public const CRM_PURCHASE_PRICE_NULL = 'purchasePrice_null';
    public const BITRIX_USER_ID_PREFIX = 'bitrixUserId-';
    public const CRM_USERS_MAP = 'crm_users_map';
    public const CRM_INTEGRATION_PAYMENT = 'integration_payment';
    public const MODULE_ID = 'intaro.retailcrm';
    public const CRM_API_HOST_OPTION = 'api_host';
    public const CRM_API_KEY_OPTION = 'api_key';
    public const CRM_ORDER_TYPES_ARR = 'order_types_arr';
    public const CRM_DELIVERY_TYPES_ARR = 'deliv_types_arr';
    public const CRM_PAYMENT_TYPES = 'pay_types_arr';
    public const CRM_PAYMENT_STATUSES = 'pay_statuses_arr';
    public const CRM_PAYMENT = 'payment_arr';
    public const CRM_ORDER_LAST_ID = 'order_last_id';
    public const CRM_ORDER_DISCHARGE = 'order_discharge';
    public const CRM_SITES_LIST = 'sites_list';
    public const CRM_ORDER_PROPS = 'order_props';
    public const CRM_LEGAL_DETAILS = 'legal_details';
    public const CRM_CUSTOM_FIELDS = 'custom_fields';
    public const CRM_CONTRAGENT_TYPE = 'contragent_type';
    public const CRM_SITES_LIST_CORPORATE = 'shops-corporate';
    public const CRM_ORDER_NUMBERS = 'order_numbers';
    public const CRM_ORDER_VAT = 'order_vat';
    public const CRM_COUPON_FIELD = 'crm_coupon_field';
    public const CRM_CANCEL_ORDER = 'cansel_order';
    public const CRM_INVENTORIES_UPLOAD = 'inventories_upload';
    public const CRM_STORES = 'stores';
    public const CRM_SHOPS = 'shops';
    public const CRM_IBLOCKS_INVENTORIES = 'iblocks_inventories';
    public const CRM_PRICES_UPLOAD = 'prices_upload';
    public const CRM_PRICES = 'prices';
    public const CRM_PRICE_SHOPS = 'price_shops';
    public const CRM_IBLOCKS_PRICES = 'iblock_prices';
    public const CRM_COLLECTOR = 'collector';
    public const CRM_COLL_KEY = 'coll_key';
    public const CRM_UA = 'ua';
    public const CRM_UA_KEYS = 'ua_keys';
    public const CRM_DISCOUNT_ROUND = 'discount_round';
    public const CRM_CC = 'cc';
    public const CRM_CORP_SHOPS = 'shops-corporate';
    public const CRM_CORP_NAME = 'nickName-corporate';
    public const CRM_CORP_ADDRESS = 'adres-corporate';
    public const CRM_API_VERSION = 'api_version';
    public const CRM_CURRENCY = 'currency';
    public const CRM_ADDRESS_OPTIONS = 'address_options';
    public const CRM_DIMENSIONS = 'order_dimensions';
    public const PROTOCOL = 'protocol';
    public const CRM_ORDER_FAILED_IDS = 'order_failed_ids';
    public const CRM_CUSTOMERS_HISTORY_SINCE_ID = 'customer_history';
    public const CRM_ORDER_HISTORY_DATE = 'order_history_date';
    public const CRM_ORDER_HISTORY = 'order_history';
    public const CRM_CATALOG_BASE_PRICE = 'catalog_base_price';
    public const CRM_ORDER_DIMENSIONS = 'order_dimensions';
    public const CRM_INTEGRATION_DELIVERY = 'integration_delivery';
    public const CRM_SHIPMENT_DEDUCTED = 'shipment_deducted';
    public const CORPORATE_CONTRAGENT_TYPE = 'legal-entity';
    public const SEND_PAYMENT_AMOUNT = 'send_payment_amount';
    public const CRM_ONLINE_CONSULTANT = 'online_consultant';
    public const CRM_ONLINE_CONSULTANT_SCRIPT = 'online_consultant_script';
    public const CRM_EVENT_TRACKER = 'event_tracker';
    public const CRM_EVENT_TRACKER_CART = 'event_tracker_cart';
    public const CRM_EVENT_TRACKER_OPEN_CART = 'event_tracker_open_cart';
    public const LOYALTY_PROGRAM_TOGGLE = 'loyalty_program_toggle';
    public const CLIENT_ID = 'client_id';
    public const AGREEMENT_LOYALTY_PROGRAM = 'agreement_loyalty_program';
    public const AGREEMENT_PERSONAL_DATA = 'agreement_personal_data';
    public const HL_LOYALTY_CODE = 'LoyaltyProgramRetailCRM';
    public const HL_LOYALTY_TABLE_NAME = 'loyalty_program';
    public const API_ERRORS_LOG = 'apiErrors';
    public const LOYALTY_ERROR = 'loyaltyErrors';
    public const REPOSITORY_ERRORS = 'repositoryErrors';
    public const TEMPLATES_ERROR = 'templatesErrors';
    public const DEFAULT_LOYALTY_TEMPLATE = 'default_loyalty';
    public const LOYALTY_PROGRAM_ID = 'LOYALTY_PROGRAM_ID';
    public const LOYALTY_FIELDS = 'loyalty_fields';
    public const AGREEMENT_PERSONAL_DATA_CODE = 'AGREEMENT_PERSONAL_DATA_CODE';
    public const AGREEMENT_LOYALTY_PROGRAM_CODE = 'AGREEMENT_LOYALTY_PROGRAM_CODE';
    public const CART = 'cart';
    public const CRM_SEND_PICKUP_POINT_ADDRESS = 'send_pickup_point_address';
    public const LP_EVENTS = [
        ['EVENT_NAME' => 'OnSaleOrderSaved', 'FROM_MODULE' => 'sale'],
        ['EVENT_NAME' => 'OnSaleComponentOrderResultPrepared', 'FROM_MODULE' => 'sale'],
    ];
    public const SITES_AVAILABLE = 'sites_available';
    public const RECEIVE_TRACK_NUMBER_DELIVERY = 'receive_track_number_delivery';
    public const CUSTOM_FIELDS_TOGGLE = 'custom_fields_toggle';
    public const MATCHED_CUSTOM_PROPS = 'matched_order_props';
    public const MATCHED_CUSTOM_USER_FIELDS = 'matched_custom_field';
    public const USE_CRM_ORDER_METHODS = 'use_crm_order_methods';
    public const CRM_ORDER_METHODS = 'crm_order_methods';
    public const SYNC_INTEGRATION_PAYMENT = 'sync_integration_payment';
    public const CRM_PART_SUBSTITUTED_PAYMENT_CODE = '-not-integration';
    public const CRM_SUBSTITUTION_PAYMENT_LIST = 'substitution_payment';
    public const REQUIRED_API_SCOPES = [
        'order_read' => 'order_read',
        'order_write' => 'order_write',
        'customer_read' => 'customer_read',
        'customer_write' => 'customer_write',
        'store_read' => 'store_read',
        'store_write' => 'store_write',
        'reference_read' => 'reference_read',
        'reference_write' => 'reference_write',
        'integration_read' => 'integration_read',
        'integration_write' => 'integration_write',
    ];
    public const REQUIRED_API_SCOPES_CUSTOM = [
        'custom_fields_read' => 'custom_fields_read',
        'custom_fields_write' => 'custom_fields_write',
    ];
    public const OPTION_FIX_DATE_CUSTOMER = 'once_upload_customer';
    public const OPTION_FIX_DATE_CUSTOMER_LAST_ID = 'last_id_customer_fix';
    public const HISTORY_TIME = 'history_time';
    public const MODULE_DEACTIVATE = 'module_deactivate';
    public const AGENTS_DEACTIVATE = 'agents_deactivate';
    public const EVENTS_DEACTIVATE = 'events_deactivate';
    public const LAST_ORDER_UPDATE = 'last_order_update';
}
