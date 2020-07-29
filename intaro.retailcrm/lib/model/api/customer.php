<?php
/**
 * PHP version 7.1
 *
 * @category Integration
 * @package  Intaro\RetailCrm\Model\Api
 * @author   retailCRM <integration@retailcrm.ru>
 * @license  MIT
 * @link     http://retailcrm.ru
 * @see      http://retailcrm.ru/docs
 */
namespace Intaro\RetailCrm\Model\Api;

use Intaro\RetailCrm\Component\Json\Mapping;

/**
 * Class Customer
 *
 * @package Intaro\RetailCrm\Model\Api
 */
class Customer extends AbstractApiModel
{
    /**
     * ID [обычного|корпоративного] клиента
     *
     * @var integer $id
     *
     * @Mapping\Type("integer")
     * @Mapping\SerializedName("id")
     */
    public $id;

    /**
     * Внешний ID [обычного|корпоративного] клиента
     *
     * @var string $externalId
     *
     * @Mapping\Type("string")
     * @Mapping\SerializedName("externalId")
     */
    public $externalId;

    /**
     * Внешний идентификатор [обычного|корпоративного] клиента в складской системе
     *
     * @var string $uuid
     *
     * @Mapping\Type("string")
     * @Mapping\SerializedName("uuid")
     */
    public $uuid;

    /**
     * Тип клиента (корпоративный или обычный)
     *
     * @var string $type
     *
     * @Mapping\Type("string")
     * @Mapping\SerializedName("type")
     */
    public $type;

    /**
     * Контактное лицо корпоративного клиента является основным
     *
     * @var string $isMain
     *
     * @Mapping\Type("boolean")
     * @Mapping\SerializedName("isMain")
     */
    public $isMain;

    /**
     * Является ли клиент контактным лицом корпоративного клиента
     *
     * @var boolean $isContact
     *
     * @Mapping\Type("boolean")
     * @Mapping\SerializedName("isContact")
     */
    public $isContact;

    /**
     * Дата создания в системе
     *
     * @var \DateTime $createdAt
     *
     * @Mapping\Type("DateTime<'Y-m-d H:i:s'>")
     * @Mapping\SerializedName("createdAt")
     */
    public $createdAt;

    /**
     * Имя
     *
     * @var string $firstName
     *
     * @Mapping\Type("string")
     * @Mapping\SerializedName("firstName")
     */
    public $firstName;

    /**
     * Фамилия
     *
     * @var string $lastName
     *
     * @Mapping\Type("string")
     * @Mapping\SerializedName("lastName")
     */
    public $lastName;

    /**
     * Отчество
     *
     * @var string $patronymic
     *
     * @Mapping\Type("string")
     * @Mapping\SerializedName("patronymic")
     */
    public $patronymic;

    /**
     * Адрес электронной почты
     *
     * @var string $email
     *
     * @Mapping\Type("string")
     * @Mapping\SerializedName("email")
     */
    public $email;

    /**
     * Телефоны
     *
     * @var array $phones
     *
     * @Mapping\Type("array<Intaro\RetailCrm\Model\Api\Phone>")
     * @Mapping\SerializedName("phones")
     */
    public $phones;

    /**
     * ID менеджера, к которому привязан клиент
     *
     * @var integer $managerId
     *
     * @Mapping\Type("integer")
     * @Mapping\SerializedName("managerId")
     */
    public $managerId;

    /**
     * Реквизиты
     *
     * @var Contragent $contragent
     *
     * @Mapping\Type("Intaro\RetailCrm\Model\Api\Contragent")
     * @Mapping\SerializedName("contragent")
     */
    public $contragent;

    /**
     * Список пользователских полей
     *
     * @var array $customFields
     *
     * @Mapping\Type("array")
     * @Mapping\SerializedName("customFields")
     */
    public $customFields;

    /**
     * Магазин, с которого пришел клиент
     *
     * @var string $site
     *
     * @Mapping\Type("string")
     * @Mapping\SerializedName("site")
     */
    public $site;

    /**
     * Наименование
     *
     * @var string $nickName
     *
     * @Mapping\Type("string")
     * @Mapping\SerializedName("nickName")
     */
    public $nickName;

    /**
     * Адрес
     *
     * @var Address $address
     *
     * @Mapping\Type("Intaro\RetailCrm\Model\Api\Address")
     * @Mapping\SerializedName("address")
     */
    public $address;

    /**
     * Адреса
     *
     * @var array $addresses
     *
     * @Mapping\Type("array<Intaro\RetailCrm\Model\Api\Address>")
     * @Mapping\SerializedName("addresses")
     */
    public $addresses;

    /**
     * Основной адрес
     *
     * @var Address $mainAddress
     *
     * @Mapping\Type("Intaro\RetailCrm\Model\Api\Address")
     * @Mapping\SerializedName("mainAddress")
     */
    public $mainAddress;

    /**
     * Компании
     *
     * @var array $companies
     *
     * @Mapping\Type("array<Intaro\RetailCrm\Model\Api\Company>")
     * @Mapping\SerializedName("companies")
     */
    public $companies;

    /**
     * Основная компания
     *
     * @var Company $mainCompany
     *
     * @Mapping\Type("Intaro\RetailCrm\Model\Api\Company")
     * @Mapping\SerializedName("mainCompany")
     */
    public $mainCompany;

    /**
     * Контактные лица
     *
     * @var array $customerContacts
     *
     * @Mapping\Type("array<Intaro\RetailCrm\Model\Api\CustomerContact>")
     * @Mapping\SerializedName("customerContacts")
     */
    public $customerContacts;

    /**
     * Основное контактное лицо
     *
     * @var CustomerContact $mainCustomerContact
     *
     * @Mapping\Type("Intaro\RetailCrm\Model\Api\CustomerContact")
     * @Mapping\SerializedName("mainCustomerContact")
     */
    public $mainCustomerContact;
}
