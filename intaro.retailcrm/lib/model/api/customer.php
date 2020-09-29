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

use DateTime;
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
    private $id;

    /**
     * Внешний ID [обычного|корпоративного] клиента
     *
     * @var string $externalId
     *
     * @Mapping\Type("string")
     * @Mapping\SerializedName("externalId")
     */
    private $externalId;

    /**
     * Внешний идентификатор [обычного|корпоративного] клиента в складской системе
     *
     * @var string $uuid
     *
     * @Mapping\Type("string")
     * @Mapping\SerializedName("uuid")
     */
    private $uuid;

    /**
     * Тип клиента (корпоративный или обычный)
     *
     * @var string $type
     *
     * @Mapping\Type("string")
     * @Mapping\SerializedName("type")
     */
    private $type;

    /**
     * Контактное лицо корпоративного клиента является основным
     *
     * @var bool $isMain
     *
     * @Mapping\Type("boolean")
     * @Mapping\SerializedName("isMain")
     */
    private $isMain;

    /**
     * Индикатор подписки на рассылку
     *
     * @var bool $subscribed
     *
     * @Mapping\Type("boolean")
     * @Mapping\SerializedName("subscribed")
     */
    private $subscribed;

    /**
     * Кука Daemon Collector
     *
     * @var bool $browserId
     *
     * @Mapping\Type("string")
     * @Mapping\SerializedName("browserId")
     */
    private $browserId;

    /**
     * Является ли клиент контактным лицом корпоративного клиента
     *
     * @var boolean $isContact
     *
     * @Mapping\Type("boolean")
     * @Mapping\SerializedName("isContact")
     */
    private $isContact;

    /**
     * Дата создания в системе
     *
     * @var \DateTime $createdAt
     *
     * @Mapping\Type("DateTime<'Y-m-d H:i:s'>")
     * @Mapping\SerializedName("createdAt")
     */
    private $createdAt;

    /**
     * Имя
     *
     * @var string $firstName
     *
     * @Mapping\Type("string")
     * @Mapping\SerializedName("firstName")
     */
    private $firstName;

    /**
     * Фамилия
     *
     * @var string $lastName
     *
     * @Mapping\Type("string")
     * @Mapping\SerializedName("lastName")
     */
    private $lastName;

    /**
     * Отчество
     *
     * @var string $patronymic
     *
     * @Mapping\Type("string")
     * @Mapping\SerializedName("patronymic")
     */
    private $patronymic;

    /**
     * Адрес электронной почты
     *
     * @var string $email
     *
     * @Mapping\Type("string")
     * @Mapping\SerializedName("email")
     */
    private $email;

    /**
     * Телефоны
     *
     * @var \Intaro\RetailCrm\Model\Api\Phone[] $phones
     *
     * @Mapping\Type("array<Intaro\RetailCrm\Model\Api\Phone>")
     * @Mapping\SerializedName("phones")
     */
    private $phones;

    /**
     * Дата рождения
     *
     * @var \DateTime
     *
     * @Mapping\Type("DateTime<'Y-m-d'>")
     * @Mapping\SerializedName("birthday")
     */
    private $birthday;

    /**
     * Пол
     *
     * @var string
     *
     * @Mapping\Type("string")
     * @Mapping\SerializedName("sex")
     */
    private $sex;

    /**
     * ID менеджера, к которому привязан клиент
     *
     * @var integer $managerId
     *
     * @Mapping\Type("integer")
     * @Mapping\SerializedName("managerId")
     */
    private $managerId;

    /**
     * Реквизиты
     *
     * @var Contragent $contragent
     *
     * @Mapping\Type("Intaro\RetailCrm\Model\Api\Contragent")
     * @Mapping\SerializedName("contragent")
     */
    private $contragent;

    /**
     * Список пользовательских полей
     *
     * @var array $customFields
     *
     * @Mapping\Type("array")
     * @Mapping\SerializedName("customFields")
     */
    private $customFields;

    /**
     * Магазин, с которого пришел клиент
     *
     * @var string $site
     *
     * @Mapping\Type("string")
     * @Mapping\SerializedName("site")
     */
    private $site;

    /**
     * Наименование
     *
     * @var string $nickName
     *
     * @Mapping\Type("string")
     * @Mapping\SerializedName("nickName")
     */
    private $nickName;

    /**
     * Адрес
     *
     * @var Address $address
     *
     * @Mapping\Type("Intaro\RetailCrm\Model\Api\Address")
     * @Mapping\SerializedName("address")
     */
    private $address;

    /**
     * Адреса
     *
     * @var array $addresses
     *
     * @Mapping\Type("array<Intaro\RetailCrm\Model\Api\Address>")
     * @Mapping\SerializedName("addresses")
     */
    private $addresses;

    /**
     * Основной адрес
     *
     * @var Address $mainAddress
     *
     * @Mapping\Type("Intaro\RetailCrm\Model\Api\Address")
     * @Mapping\SerializedName("mainAddress")
     */
    private $mainAddress;

    /**
     * Компании
     *
     * @var array $companies
     *
     * @Mapping\Type("array<Intaro\RetailCrm\Model\Api\Company>")
     * @Mapping\SerializedName("companies")
     */
    private $companies;

    /**
     * Основная компания
     *
     * @var Company $mainCompany
     *
     * @Mapping\Type("Intaro\RetailCrm\Model\Api\Company")
     * @Mapping\SerializedName("mainCompany")
     */
    private $mainCompany;

    /**
     * Контактные лица
     *
     * @var array $customerContacts
     *
     * @Mapping\Type("array<Intaro\RetailCrm\Model\Api\CustomerContact>")
     * @Mapping\SerializedName("customerContacts")
     */
    private $customerContacts;

    /**
     * Основное контактное лицо
     *
     * @var CustomerContact $mainCustomerContact
     *
     * @Mapping\Type("Intaro\RetailCrm\Model\Api\CustomerContact")
     * @Mapping\SerializedName("mainCustomerContact")
     */
    private $mainCustomerContact;

    /**
     * Персональная скидка
     *
     * @var double $mainCustomerContact
     *
     * @Mapping\Type("double")
     * @Mapping\SerializedName("personalDiscount")
     */
    private $personalDiscount;
    
    /**
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
    }
    
    /**
     * @param int $id
     */
    public function setId(int $id): void
    {
        $this->id = $id;
    }
    
    /**
     * @return float
     */
    public function getPersonalDiscount(): float
    {
        return $this->personalDiscount;
    }
    
    /**
     * @param float $personalDiscount
     */
    public function setPersonalDiscount(float $personalDiscount): void
    {
        $this->personalDiscount = $personalDiscount;
    }
    
    /**
     * @return \Intaro\RetailCrm\Model\Api\CustomerContact
     */
    public function getMainCustomerContact(): \Intaro\RetailCrm\Model\Api\CustomerContact
    {
        return $this->mainCustomerContact;
    }
    
    /**
     * @param \Intaro\RetailCrm\Model\Api\CustomerContact $mainCustomerContact
     */
    public function setMainCustomerContact(\Intaro\RetailCrm\Model\Api\CustomerContact $mainCustomerContact): void
    {
        $this->mainCustomerContact = $mainCustomerContact;
    }
    
    /**
     * @return array
     */
    public function getCustomerContacts(): array
    {
        return $this->customerContacts;
    }
    
    /**
     * @param array $customerContacts
     */
    public function setCustomerContacts(array $customerContacts): void
    {
        $this->customerContacts = $customerContacts;
    }
    
    /**
     * @return \Intaro\RetailCrm\Model\Api\Company
     */
    public function getMainCompany(): \Intaro\RetailCrm\Model\Api\Company
    {
        return $this->mainCompany;
    }
    
    /**
     * @param \Intaro\RetailCrm\Model\Api\Company $mainCompany
     */
    public function setMainCompany(\Intaro\RetailCrm\Model\Api\Company $mainCompany): void
    {
        $this->mainCompany = $mainCompany;
    }
    
    /**
     * @return array
     */
    public function getCompanies(): array
    {
        return $this->companies;
    }
    
    /**
     * @param array $companies
     */
    public function setCompanies(array $companies): void
    {
        $this->companies = $companies;
    }
    
    /**
     * @return \Intaro\RetailCrm\Model\Api\Address
     */
    public function getMainAddress(): \Intaro\RetailCrm\Model\Api\Address
    {
        return $this->mainAddress;
    }
    
    /**
     * @param \Intaro\RetailCrm\Model\Api\Address $mainAddress
     */
    public function setMainAddress(\Intaro\RetailCrm\Model\Api\Address $mainAddress): void
    {
        $this->mainAddress = $mainAddress;
    }
    
    /**
     * @return array
     */
    public function getAddresses(): array
    {
        return $this->addresses;
    }
    
    /**
     * @param array $addresses
     */
    public function setAddresses(array $addresses): void
    {
        $this->addresses = $addresses;
    }
    
    /**
     * @return \Intaro\RetailCrm\Model\Api\Address
     */
    public function getAddress(): \Intaro\RetailCrm\Model\Api\Address
    {
        return $this->address;
    }
    
    /**
     * @param \Intaro\RetailCrm\Model\Api\Address $address
     */
    public function setAddress(\Intaro\RetailCrm\Model\Api\Address $address): void
    {
        $this->address = $address;
    }
    
    /**
     * @return array
     */
    public function getCustomFields(): array
    {
        return $this->customFields;
    }
    
    /**
     * @param array $customFields
     */
    public function setCustomFields(array $customFields): void
    {
        $this->customFields = $customFields;
    }
    
    /**
     * @return string
     */
    public function getNickName(): string
    {
        return $this->nickName;
    }
    
    /**
     * @param string $nickName
     */
    public function setNickName(string $nickName): void
    {
        $this->nickName = $nickName;
    }
    
    /**
     * @return string
     */
    public function getSite(): string
    {
        return $this->site;
    }
    
    /**
     * @param string $site
     */
    public function setSite(string $site): void
    {
        $this->site = $site;
    }
    
    /**
     * @return \Intaro\RetailCrm\Model\Api\Contragent
     */
    public function getContragent(): \Intaro\RetailCrm\Model\Api\Contragent
    {
        return $this->contragent;
    }
    
    /**
     * @param \Intaro\RetailCrm\Model\Api\Contragent $contragent
     */
    public function setContragent(\Intaro\RetailCrm\Model\Api\Contragent $contragent): void
    {
        $this->contragent = $contragent;
    }
    
    /**
     * @return int
     */
    public function getManagerId(): int
    {
        return $this->managerId;
    }
    
    /**
     * @param int $managerId
     */
    public function setManagerId(int $managerId): void
    {
        $this->managerId = $managerId;
    }
    
    /**
     * @return string
     */
    public function getSex(): string
    {
        return $this->sex;
    }
    
    /**
     * @param string $sex
     */
    public function setSex(string $sex): void
    {
        $this->sex = $sex;
    }
    
    /**
     * @return \DateTime
     */
    public function getBirthday(): DateTime
    {
        return $this->birthday;
    }
    
    /**
     * @param \DateTime $birthday
     */
    public function setBirthday(DateTime $birthday): void
    {
        $this->birthday = $birthday;
    }
    
    /**
     * @return \Intaro\RetailCrm\Model\Api\Phone[]
     */
    public function getPhones(): array
    {
        return $this->phones;
    }
    
    /**
     * @param \Intaro\RetailCrm\Model\Api\Phone[] $phones
     */
    public function setPhones(array $phones): void
    {
        $this->phones = $phones;
    }
    
    /**
     * @return string
     */
    public function getEmail(): string
    {
        return $this->email;
    }
    
    /**
     * @param string $email
     */
    public function setEmail(string $email): void
    {
        $this->email = $email;
    }
    
    /**
     * @return string
     */
    public function getPatronymic(): string
    {
        return $this->patronymic;
    }
    
    /**
     * @param string $patronymic
     */
    public function setPatronymic(string $patronymic): void
    {
        $this->patronymic = $patronymic;
    }
    
    /**
     * @return string
     */
    public function getLastName(): string
    {
        return $this->lastName;
    }
    
    /**
     * @param string $lastName
     */
    public function setLastName(string $lastName): void
    {
        $this->lastName = $lastName;
    }
    
    /**
     * @return string
     */
    public function getFirstName(): string
    {
        return $this->firstName;
    }
    
    /**
     * @param string $firstName
     */
    public function setFirstName(string $firstName): void
    {
        $this->firstName = $firstName;
    }
    
    /**
     * @return \DateTime
     */
    public function getCreatedAt(): DateTime
    {
        return $this->createdAt;
    }
    
    /**
     * @param \DateTime $createdAt
     */
    public function setCreatedAt(DateTime $createdAt): void
    {
        $this->createdAt = $createdAt;
    }
    
    /**
     * @return bool
     */
    public function isContact(): bool
    {
        return $this->isContact;
    }
    
    /**
     * @param bool $isContact
     */
    public function setIsContact(bool $isContact): void
    {
        $this->isContact = $isContact;
    }
    
    /**
     * @return bool
     */
    public function isBrowserId(): bool
    {
        return $this->browserId;
    }
    
    /**
     * @param bool $browserId
     */
    public function setBrowserId(bool $browserId): void
    {
        $this->browserId = $browserId;
    }
    
    /**
     * @return bool
     */
    public function isSubscribed(): bool
    {
        return $this->subscribed;
    }
    
    /**
     * @param bool $subscribed
     */
    public function setSubscribed(bool $subscribed): void
    {
        $this->subscribed = $subscribed;
    }
    
    /**
     * @return bool
     */
    public function isMain(): bool
    {
        return $this->isMain;
    }
    
    /**
     * @param bool $isMain
     */
    public function setIsMain(bool $isMain): void
    {
        $this->isMain = $isMain;
    }
    
    /**
     * @return string
     */
    public function getType(): string
    {
        return $this->type;
    }
    
    /**
     * @param string $type
     */
    public function setType(string $type): void
    {
        $this->type = $type;
    }
    
    /**
     * @return string
     */
    public function getUuid(): string
    {
        return $this->uuid;
    }
    
    /**
     * @param string $uuid
     */
    public function setUuid(string $uuid): void
    {
        $this->uuid = $uuid;
    }
    
    /**
     * @return string
     */
    public function getExternalId(): string
    {
        return $this->externalId;
    }
    
    /**
     * @param string $externalId
     */
    public function setExternalId(string $externalId): void
    {
        $this->externalId = $externalId;
    }
}
