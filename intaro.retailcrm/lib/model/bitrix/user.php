<?php
/**
 * PHP version 7.1
 *
 * @category Integration
 * @package  Intaro\RetailCrm\Model\Bitrix
 * @author   retailCRM <integration@retailcrm.ru>
 * @license  MIT
 * @link     http://retailcrm.ru
 * @see      http://retailcrm.ru/docs
 */
namespace Intaro\RetailCrm\Model\Bitrix;

use Bitrix\Main\Type\DateTime;
use Intaro\RetailCrm\Component\Json\Mapping;

/**
 * Class User
 *
 * @package Intaro\RetailCrm\Model\Bitrix
 */
class User extends AbstractSerializableModel
{
    /**
     * @var int
     *
     * @Mapping\Type("int")
     * @Mapping\SerializedName("ID")
     */
    private $id;

    /**
     * @var string
     *
     * @Mapping\Type("string")
     * @Mapping\SerializedName("LOGIN")
     */
    private $login;

    /**
     * @var string
     *
     * @Mapping\Type("string")
     * @Mapping\SerializedName("PASSWORD")
     */
    private $password;

    /**
     * @var string
     *
     * @Mapping\Type("string")
     * @Mapping\SerializedName("EMAIL")
     */
    private $email;

    /**
     * @var bool
     *
     * @Mapping\Type("bool")
     * @Mapping\SerializedName("ACTIVE")
     */
    private $active;

    /**
     * @var \DateTime|null
     *
     * @Mapping\Type("DateTime<'m.d.Y H:i:s'>")
     * @Mapping\SerializedName("DATE_REGISTER")
     */
    private $dateRegister;

    /**
     * @var DateTime
     *
     * @Mapping\Type("DateTime")
     * @Mapping\SerializedName("DATE_REG_SHORT")
     * @Mapping\NoTransform()
     */
    private $dateRegShort;

    /**
     * @var \DateTime
     *
     * @Mapping\Type("DateTime<'m.d.Y H:i:s'>")
     * @Mapping\SerializedName("LAST_LOGIN")
     */
    private $lastLogin;

    /**
     * @var DateTime
     *
     * @Mapping\Type("DateTime")
     * @Mapping\SerializedName("LAST_LOGIN_SHORT")
     * @Mapping\NoTransform()
     */
    private $lastLoginShort;

    /**
     * @var \DateTime
     *
     * @Mapping\Type("DateTime<'m.d.Y H:i:s'>")
     * @Mapping\SerializedName("LAST_ACTIVITY_DATE")
     */
    private $lastActivityDate;

    /**
     * @var \DateTime
     *
     * @Mapping\Type("DateTime<'m.d.Y H:i:s'>")
     * @Mapping\SerializedName("TIMESTAMP_X")
     */
    private $timestampX;

    /**
     * @var string
     *
     * @Mapping\Type("string")
     * @Mapping\SerializedName("NAME")
     */
    private $name;

    /**
     * @var string
     *
     * @Mapping\Type("string")
     * @Mapping\SerializedName("SECOND_NAME")
     */
    private $secondName;

    /**
     * @var string
     *
     * @Mapping\Type("string")
     * @Mapping\SerializedName("LAST_NAME")
     */
    private $lastName;

    /**
     * @var string
     *
     * @Mapping\Type("string")
     * @Mapping\SerializedName("TITLE")
     */
    private $title;

    /**
     * @var string
     *
     * @Mapping\Type("string")
     * @Mapping\SerializedName("EXTERNAL_AUTH_ID")
     */
    private $externalAuthId;

    /**
     * @var string
     *
     * @Mapping\Type("string")
     * @Mapping\SerializedName("XML_ID")
     */
    private $xmlId;

    /**
     * @var string
     *
     * @Mapping\Type("string")
     * @Mapping\SerializedName("BX_USER_ID")
     */
    private $bxUserId;

    /**
     * @var string
     *
     * @Mapping\Type("string")
     * @Mapping\SerializedName("CONFIRM_CODE")
     */
    private $confirmCode;

    /**
     * @var string
     *
     * @Mapping\Type("string")
     * @Mapping\SerializedName("LID")
     */
    private $lid;

    /**
     * @var string
     *
     * @Mapping\Type("string")
     * @Mapping\SerializedName("LANGUAGE_ID")
     */
    private $languageId;

    /**
     * @var int
     *
     * @Mapping\Type("int")
     * @Mapping\SerializedName("TIME_ZONE_OFFSET")
     */
    private $timeZoneOffset;

    /**
     * @var string
     *
     * @Mapping\Type("string")
     * @Mapping\SerializedName("PERSONAL_PROFESSION")
     */
    private $personalProfession;

    /**
     * @var string
     *
     * @Mapping\Type("string")
     * @Mapping\SerializedName("PERSONAL_PHONE")
     */
    private $personalPhone;

    /**
     * @var string
     *
     * @Mapping\Type("string")
     * @Mapping\SerializedName("PERSONAL_MOBILE")
     */
    private $personalMobile;

    /**
     * @var string
     *
     * @Mapping\Type("string")
     * @Mapping\SerializedName("PERSONAL_WWW")
     */
    private $personalWww;

    /**
     * @var string
     *
     * @Mapping\Type("string")
     * @Mapping\SerializedName("PERSONAL_ICQ")
     */
    private $personalIcq;

    /**
     * @var string
     *
     * @Mapping\Type("string")
     * @Mapping\SerializedName("PERSONAL_FAX")
     */
    private $personalFax;

    /**
     * @var string
     *
     * @Mapping\Type("string")
     * @Mapping\SerializedName("PERSONAL_PAGER")
     */
    private $personalPager;

    /**
     * @var string
     *
     * @Mapping\Type("string")
     * @Mapping\SerializedName("PERSONAL_STREET")
     */
    private $personalStreet;

    /**
     * @var string
     *
     * @Mapping\Type("string")
     * @Mapping\SerializedName("PERSONAL_MAILBOX")
     */
    private $personalMailbox;

    /**
     * @var string
     *
     * @Mapping\Type("string")
     * @Mapping\SerializedName("PERSONAL_CITY")
     */
    private $personalCity;

    /**
     * @var string
     *
     * @Mapping\Type("string")
     * @Mapping\SerializedName("PERSONAL_STATE")
     */
    private $personalState;

    /**
     * @var string
     *
     * @Mapping\Type("string")
     * @Mapping\SerializedName("PERSONAL_ZIP")
     */
    private $personalZip;

    /**
     * @var string
     *
     * @Mapping\Type("string")
     * @Mapping\SerializedName("PERSONAL_COUNTRY")
     */
    private $personalCountry;

    /**
     * @var DateTime
     * // TODO: Replace $personalBirthday with \DateTime
     * @Mapping\Type("DateTime")
     * @Mapping\SerializedName("PERSONAL_BIRTHDAY")
     * @Mapping\NoTransform()
     */
    private $personalBirthday;

    /**
     * @var string
     *
     * @Mapping\Type("string")
     * @Mapping\SerializedName("PERSONAL_GENDER")
     */
    private $personalGender;

    /**
     * @var int
     *
     * @Mapping\Type("int")
     * @Mapping\SerializedName("PERSONAL_PHOTO")
     */
    private $personalPhoto;

    /**
     * @var string
     *
     * @Mapping\Type("string")
     * @Mapping\SerializedName("PERSONAL_NOTES")
     */
    private $personalNotes;

    /**
     * @var string
     *
     * @Mapping\Type("string")
     * @Mapping\SerializedName("WORK_COMPANY")
     */
    private $workCompany;

    /**
     * @var string
     *
     * @Mapping\Type("string")
     * @Mapping\SerializedName("WORK_DEPARTMENT")
     */
    private $workDepartment;

    /**
     * @var string
     *
     * @Mapping\Type("string")
     * @Mapping\SerializedName("WORK_PHONE")
     */
    private $workPhone;

    /**
     * @var string
     *
     * @Mapping\Type("string")
     * @Mapping\SerializedName("WORK_POSITION")
     */
    private $workPosition;

    /**
     * @var string
     *
     * @Mapping\Type("string")
     * @Mapping\SerializedName("WORK_WWW")
     */
    private $workWww;

    /**
     * @var string
     *
     * @Mapping\Type("string")
     * @Mapping\SerializedName("WORK_FAX")
     */
    private $workFax;

    /**
     * @var string
     *
     * @Mapping\Type("string")
     * @Mapping\SerializedName("WORK_PAGER")
     */
    private $workPager;

    /**
     * @var string
     *
     * @Mapping\Type("string")
     * @Mapping\SerializedName("WORK_STREET")
     */
    private $workStreet;

    /**
     * @var string
     *
     * @Mapping\Type("string")
     * @Mapping\SerializedName("WORK_MAILBOX")
     */
    private $workMailbox;

    /**
     * @var string
     *
     * @Mapping\Type("string")
     * @Mapping\SerializedName("WORK_CITY")
     */
    private $workCity;

    /**
     * @var string
     *
     * @Mapping\Type("string")
     * @Mapping\SerializedName("WORK_STATE")
     */
    private $workState;

    /**
     * @var string
     *
     * @Mapping\Type("string")
     * @Mapping\SerializedName("WORK_ZIP")
     */
    private $workZip;

    /**
     * @var string
     *
     * @Mapping\Type("string")
     * @Mapping\SerializedName("WORK_COUNTRY")
     */
    private $workCountry;

    /**
     * @var string
     *
     * @Mapping\Type("string")
     * @Mapping\SerializedName("WORK_PROFILE")
     */
    private $workProfile;

    /**
     * @var int
     *
     * @Mapping\Type("int")
     * @Mapping\SerializedName("WORK_LOGO")
     */
    private $workLogo;

    /**
     * @var string
     *
     * @Mapping\Type("string")
     * @Mapping\SerializedName("WORK_NOTES")
     */
    private $workNotes;

    /**
     * @var string
     *
     * @Mapping\Type("string")
     * @Mapping\SerializedName("ADMIN_NOTES")
     */
    private $adminNotes;

    /**
     * @var string
     *
     * @Mapping\Type("string")
     * @Mapping\SerializedName("SHORT_NAME")
     */
    private $shortName;

    /**
     * @var bool
     *
     * @Mapping\Type("bool")
     * @Mapping\SerializedName("IS_ONLINE")
     */
    private $isOnline;

    /**
     * @var bool
     *
     * @Mapping\Type("bool")
     * @Mapping\SerializedName("IS_REAL_USER")
     */
    private $isRealUser;

    /**
     * @var mixed
     *
     * @Mapping\Type("mixed")
     * @Mapping\SerializedName("INDEX")
     */
    private $index;

    /**
     * @var mixed
     *
     * @Mapping\Type("mixed")
     * @Mapping\SerializedName("INDEX_SELECTOR")
     */
    private $indexSelector;

    /**
     * @return int
     */
    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * @param int $id
     *
     * @return User
     */
    public function setId(int $id): ?User
    {
        $this->id = $id;
        return $this;
    }

    /**
     * @return string
     */
    public function getLogin(): ?string
    {
        return $this->login;
    }

    /**
     * @param string $login
     *
     * @return User
     */
    public function setLogin(string $login): ?User
    {
        $this->login = $login;
        return $this;
    }

    /**
     * @return string
     */
    public function getPassword(): ?string
    {
        return $this->password;
    }

    /**
     * @param string $password
     *
     * @return User
     */
    public function setPassword(string $password): ?User
    {
        $this->password = $password;
        return $this;
    }

    /**
     * @return string
     */
    public function getEmail(): ?string
    {
        return $this->email;
    }

    /**
     * @param string $email
     *
     * @return User
     */
    public function setEmail(string $email): ?User
    {
        $this->email = $email;
        return $this;
    }

    /**
     * @return bool
     */
    public function getActive(): ?bool
    {
        return $this->active;
    }

    /**
     * @param bool $active
     *
     * @return User
     */
    public function setActive(bool $active): ?User
    {
        $this->active = $active;
        return $this;
    }

    /**
     * @return \DateTime|null
     */
    public function getDateRegister(): ?\DateTime
    {
        return $this->dateRegister;
    }

    /**
     * @param \DateTime $dateRegister
     *
     * @return User
     */
    public function setDateRegister(\DateTime $dateRegister): ?User
    {
        $this->dateRegister = $dateRegister;
        return $this;
    }

    /**
     * @return \Bitrix\Main\Type\DateTime
     */
    public function getDateRegShort()
    {
        return $this->dateRegShort;
    }

    /**
     * @param \Bitrix\Main\Type\DateTime $dateRegShort
     *
     * @return User
     */
    public function setDateRegShort(\Bitrix\Main\Type\DateTime $dateRegShort): ?User
    {
        $this->dateRegShort = $dateRegShort;
        return $this;
    }

    /**
     * @return \DateTime|null
     */
    public function getLastLogin(): ?\DateTime
    {
        return $this->lastLogin;
    }

    /**
     * @param \DateTime $lastLogin
     *
     * @return User
     */
    public function setLastLogin(\DateTime $lastLogin): ?User
    {
        $this->lastLogin = $lastLogin;
        return $this;
    }

    /**
     * @return \Bitrix\Main\Type\DateTime
     */
    public function getLastLoginShort()
    {
        return $this->lastLoginShort;
    }

    /**
     * @param \Bitrix\Main\Type\DateTime $lastLoginShort
     *
     * @return User
     */
    public function setLastLoginShort(\Bitrix\Main\Type\DateTime $lastLoginShort): ?User
    {
        $this->lastLoginShort = $lastLoginShort;
        return $this;
    }

    /**
     * @return \DateTime
     */
    public function getLastActivityDate(): ?\DateTime
    {
        return $this->lastActivityDate;
    }

    /**
     * @param \DateTime $lastActivityDate
     *
     * @return User
     */
    public function setLastActivityDate(\DateTime $lastActivityDate): ?User
    {
        $this->lastActivityDate = $lastActivityDate;
        return $this;
    }

    /**
     * @return \DateTime
     */
    public function getTimestampX(): ?\DateTime
    {
        return $this->timestampX;
    }

    /**
     * @param \DateTime $timestampX
     *
     * @return User
     */
    public function setTimestampX(\DateTime $timestampX): ?User
    {
        $this->timestampX = $timestampX;
        return $this;
    }

    /**
     * @return string
     */
    public function getName(): ?string
    {
        return $this->name;
    }

    /**
     * @param string $name
     *
     * @return User
     */
    public function setName(string $name): ?User
    {
        $this->name = $name;
        return $this;
    }

    /**
     * @return string
     */
    public function getSecondName(): ?string
    {
        return $this->secondName;
    }

    /**
     * @param string $secondName
     *
     * @return User
     */
    public function setSecondName(string $secondName): ?User
    {
        $this->secondName = $secondName;
        return $this;
    }

    /**
     * @return string
     */
    public function getLastName(): ?string
    {
        return $this->lastName;
    }

    /**
     * @param string $lastName
     *
     * @return User
     */
    public function setLastName(string $lastName): ?User
    {
        $this->lastName = $lastName;
        return $this;
    }

    /**
     * @return string
     */
    public function getTitle(): ?string
    {
        return $this->title;
    }

    /**
     * @param string $title
     *
     * @return User
     */
    public function setTitle(string $title): ?User
    {
        $this->title = $title;
        return $this;
    }

    /**
     * @return string
     */
    public function getExternalAuthId(): ?string
    {
        return $this->externalAuthId;
    }

    /**
     * @param string $externalAuthId
     *
     * @return User
     */
    public function setExternalAuthId(string $externalAuthId): ?User
    {
        $this->externalAuthId = $externalAuthId;
        return $this;
    }

    /**
     * @return string
     */
    public function getXmlId(): ?string
    {
        return $this->xmlId;
    }

    /**
     * @param string $xmlId
     *
     * @return User
     */
    public function setXmlId(string $xmlId): ?User
    {
        $this->xmlId = $xmlId;
        return $this;
    }

    /**
     * @return string
     */
    public function getBxUserId(): ?string
    {
        return $this->bxUserId;
    }

    /**
     * @param string $bxUserId
     *
     * @return User
     */
    public function setBxUserId(string $bxUserId): ?User
    {
        $this->bxUserId = $bxUserId;
        return $this;
    }

    /**
     * @return string
     */
    public function getConfirmCode(): ?string
    {
        return $this->confirmCode;
    }

    /**
     * @param string $confirmCode
     *
     * @return User
     */
    public function setConfirmCode(string $confirmCode): ?User
    {
        $this->confirmCode = $confirmCode;
        return $this;
    }

    /**
     * @return string
     */
    public function getLid(): ?string
    {
        return $this->lid;
    }

    /**
     * @param string $lid
     *
     * @return User
     */
    public function setLid(string $lid): ?User
    {
        $this->lid = $lid;
        return $this;
    }

    /**
     * @return string
     */
    public function getLanguageId(): ?string
    {
        return $this->languageId;
    }

    /**
     * @param string $languageId
     *
     * @return User
     */
    public function setLanguageId(string $languageId): ?User
    {
        $this->languageId = $languageId;
        return $this;
    }

    /**
     * @return int
     */
    public function getTimeZoneOffset(): ?int
    {
        return $this->timeZoneOffset;
    }

    /**
     * @param int $timeZoneOffset
     *
     * @return User
     */
    public function setTimeZoneOffset(int $timeZoneOffset): ?User
    {
        $this->timeZoneOffset = $timeZoneOffset;
        return $this;
    }

    /**
     * @return string
     */
    public function getPersonalProfession(): ?string
    {
        return $this->personalProfession;
    }

    /**
     * @param string $personalProfession
     *
     * @return User
     */
    public function setPersonalProfession(string $personalProfession): ?User
    {
        $this->personalProfession = $personalProfession;
        return $this;
    }

    /**
     * @return string
     */
    public function getPersonalPhone(): ?string
    {
        return $this->personalPhone;
    }

    /**
     * @param string $personalPhone
     *
     * @return User
     */
    public function setPersonalPhone(string $personalPhone): ?User
    {
        $this->personalPhone = $personalPhone;
        return $this;
    }

    /**
     * @return string
     */
    public function getPersonalMobile(): ?string
    {
        return $this->personalMobile;
    }

    /**
     * @param string $personalMobile
     *
     * @return User
     */
    public function setPersonalMobile(string $personalMobile): ?User
    {
        $this->personalMobile = $personalMobile;
        return $this;
    }

    /**
     * @return string
     */
    public function getPersonalWww(): ?string
    {
        return $this->personalWww;
    }

    /**
     * @param string $personalWww
     *
     * @return User
     */
    public function setPersonalWww(string $personalWww): ?User
    {
        $this->personalWww = $personalWww;
        return $this;
    }

    /**
     * @return string
     */
    public function getPersonalIcq(): ?string
    {
        return $this->personalIcq;
    }

    /**
     * @param string $personalIcq
     *
     * @return User
     */
    public function setPersonalIcq(string $personalIcq): ?User
    {
        $this->personalIcq = $personalIcq;
        return $this;
    }

    /**
     * @return string
     */
    public function getPersonalFax(): ?string
    {
        return $this->personalFax;
    }

    /**
     * @param string $personalFax
     *
     * @return User
     */
    public function setPersonalFax(string $personalFax): ?User
    {
        $this->personalFax = $personalFax;
        return $this;
    }

    /**
     * @return string
     */
    public function getPersonalPager(): ?string
    {
        return $this->personalPager;
    }

    /**
     * @param string $personalPager
     *
     * @return User
     */
    public function setPersonalPager(string $personalPager): ?User
    {
        $this->personalPager = $personalPager;
        return $this;
    }

    /**
     * @return string
     */
    public function getPersonalStreet(): ?string
    {
        return $this->personalStreet;
    }

    /**
     * @param string $personalStreet
     *
     * @return User
     */
    public function setPersonalStreet(string $personalStreet): ?User
    {
        $this->personalStreet = $personalStreet;
        return $this;
    }

    /**
     * @return string
     */
    public function getPersonalMailbox(): ?string
    {
        return $this->personalMailbox;
    }

    /**
     * @param string $personalMailbox
     *
     * @return User
     */
    public function setPersonalMailbox(string $personalMailbox): ?User
    {
        $this->personalMailbox = $personalMailbox;
        return $this;
    }

    /**
     * @return string
     */
    public function getPersonalCity(): ?string
    {
        return $this->personalCity;
    }

    /**
     * @param string $personalCity
     *
     * @return User
     */
    public function setPersonalCity(string $personalCity): ?User
    {
        $this->personalCity = $personalCity;
        return $this;
    }

    /**
     * @return string
     */
    public function getPersonalState(): ?string
    {
        return $this->personalState;
    }

    /**
     * @param string $personalState
     *
     * @return User
     */
    public function setPersonalState(string $personalState): ?User
    {
        $this->personalState = $personalState;
        return $this;
    }

    /**
     * @return string
     */
    public function getPersonalZip(): ?string
    {
        return $this->personalZip;
    }

    /**
     * @param string $personalZip
     *
     * @return User
     */
    public function setPersonalZip(string $personalZip): ?User
    {
        $this->personalZip = $personalZip;
        return $this;
    }

    /**
     * @return string
     */
    public function getPersonalCountry(): ?string
    {
        return $this->personalCountry;
    }

    /**
     * @param string $personalCountry
     *
     * @return User
     */
    public function setPersonalCountry(string $personalCountry): ?User
    {
        $this->personalCountry = $personalCountry;
        return $this;
    }

    /**
     * @return \Bitrix\Main\Type\DateTime
     */
    public function getPersonalBirthday()
    {
        return $this->personalBirthday;
    }

    /**
     * @param \Bitrix\Main\Type\DateTime $personalBirthday
     *
     * @return User
     */
    public function setPersonalBirthday(\Bitrix\Main\Type\DateTime $personalBirthday): ?User
    {
        $this->personalBirthday = $personalBirthday;
        return $this;
    }

    /**
     * @return string
     */
    public function getPersonalGender(): ?string
    {
        return $this->personalGender;
    }

    /**
     * @param string $personalGender
     *
     * @return User
     */
    public function setPersonalGender(string $personalGender): ?User
    {
        $this->personalGender = $personalGender;
        return $this;
    }

    /**
     * @return int
     */
    public function getPersonalPhoto(): ?int
    {
        return $this->personalPhoto;
    }

    /**
     * @param int $personalPhoto
     *
     * @return User
     */
    public function setPersonalPhoto(int $personalPhoto): ?User
    {
        $this->personalPhoto = $personalPhoto;
        return $this;
    }

    /**
     * @return string
     */
    public function getPersonalNotes(): ?string
    {
        return $this->personalNotes;
    }

    /**
     * @param string $personalNotes
     *
     * @return User
     */
    public function setPersonalNotes(string $personalNotes): ?User
    {
        $this->personalNotes = $personalNotes;
        return $this;
    }

    /**
     * @return string
     */
    public function getWorkCompany(): ?string
    {
        return $this->workCompany;
    }

    /**
     * @param string $workCompany
     *
     * @return User
     */
    public function setWorkCompany(string $workCompany): ?User
    {
        $this->workCompany = $workCompany;
        return $this;
    }

    /**
     * @return string
     */
    public function getWorkDepartment(): ?string
    {
        return $this->workDepartment;
    }

    /**
     * @param string $workDepartment
     *
     * @return User
     */
    public function setWorkDepartment(string $workDepartment): ?User
    {
        $this->workDepartment = $workDepartment;
        return $this;
    }

    /**
     * @return string
     */
    public function getWorkPhone(): ?string
    {
        return $this->workPhone;
    }

    /**
     * @param string $workPhone
     *
     * @return User
     */
    public function setWorkPhone(string $workPhone): ?User
    {
        $this->workPhone = $workPhone;
        return $this;
    }

    /**
     * @return string
     */
    public function getWorkPosition(): ?string
    {
        return $this->workPosition;
    }

    /**
     * @param string $workPosition
     *
     * @return User
     */
    public function setWorkPosition(string $workPosition): ?User
    {
        $this->workPosition = $workPosition;
        return $this;
    }

    /**
     * @return string
     */
    public function getWorkWww(): ?string
    {
        return $this->workWww;
    }

    /**
     * @param string $workWww
     *
     * @return User
     */
    public function setWorkWww(string $workWww): ?User
    {
        $this->workWww = $workWww;
        return $this;
    }

    /**
     * @return string
     */
    public function getWorkFax(): ?string
    {
        return $this->workFax;
    }

    /**
     * @param string $workFax
     *
     * @return User
     */
    public function setWorkFax(string $workFax): ?User
    {
        $this->workFax = $workFax;
        return $this;
    }

    /**
     * @return string
     */
    public function getWorkPager(): ?string
    {
        return $this->workPager;
    }

    /**
     * @param string $workPager
     *
     * @return User
     */
    public function setWorkPager(string $workPager): ?User
    {
        $this->workPager = $workPager;
        return $this;
    }

    /**
     * @return string
     */
    public function getWorkStreet(): ?string
    {
        return $this->workStreet;
    }

    /**
     * @param string $workStreet
     *
     * @return User
     */
    public function setWorkStreet(string $workStreet): ?User
    {
        $this->workStreet = $workStreet;
        return $this;
    }

    /**
     * @return string
     */
    public function getWorkMailbox(): ?string
    {
        return $this->workMailbox;
    }

    /**
     * @param string $workMailbox
     *
     * @return User
     */
    public function setWorkMailbox(string $workMailbox): ?User
    {
        $this->workMailbox = $workMailbox;
        return $this;
    }

    /**
     * @return string
     */
    public function getWorkCity(): ?string
    {
        return $this->workCity;
    }

    /**
     * @param string $workCity
     *
     * @return User
     */
    public function setWorkCity(string $workCity): ?User
    {
        $this->workCity = $workCity;
        return $this;
    }

    /**
     * @return string
     */
    public function getWorkState(): ?string
    {
        return $this->workState;
    }

    /**
     * @param string $workState
     *
     * @return User
     */
    public function setWorkState(string $workState): ?User
    {
        $this->workState = $workState;
        return $this;
    }

    /**
     * @return string
     */
    public function getWorkZip(): ?string
    {
        return $this->workZip;
    }

    /**
     * @param string $workZip
     *
     * @return User
     */
    public function setWorkZip(string $workZip): ?User
    {
        $this->workZip = $workZip;
        return $this;
    }

    /**
     * @return string
     */
    public function getWorkCountry(): ?string
    {
        return $this->workCountry;
    }

    /**
     * @param string $workCountry
     *
     * @return User
     */
    public function setWorkCountry(string $workCountry): ?User
    {
        $this->workCountry = $workCountry;
        return $this;
    }

    /**
     * @return string
     */
    public function getWorkProfile(): ?string
    {
        return $this->workProfile;
    }

    /**
     * @param string $workProfile
     *
     * @return User
     */
    public function setWorkProfile(string $workProfile): ?User
    {
        $this->workProfile = $workProfile;
        return $this;
    }

    /**
     * @return int
     */
    public function getWorkLogo(): ?int
    {
        return $this->workLogo;
    }

    /**
     * @param int $workLogo
     *
     * @return User
     */
    public function setWorkLogo(int $workLogo): ?User
    {
        $this->workLogo = $workLogo;
        return $this;
    }

    /**
     * @return string
     */
    public function getWorkNotes(): ?string
    {
        return $this->workNotes;
    }

    /**
     * @param string $workNotes
     *
     * @return User
     */
    public function setWorkNotes(string $workNotes): ?User
    {
        $this->workNotes = $workNotes;
        return $this;
    }

    /**
     * @return string
     */
    public function getAdminNotes(): ?string
    {
        return $this->adminNotes;
    }

    /**
     * @param string $adminNotes
     *
     * @return User
     */
    public function setAdminNotes(string $adminNotes): ?User
    {
        $this->adminNotes = $adminNotes;
        return $this;
    }

    /**
     * @return string
     */
    public function getShortName(): ?string
    {
        return $this->shortName;
    }

    /**
     * @param string $shortName
     *
     * @return User
     */
    public function setShortName(string $shortName): ?User
    {
        $this->shortName = $shortName;
        return $this;
    }

    /**
     * @return bool
     */
    public function isOnline(): ?bool
    {
        return $this->isOnline;
    }

    /**
     * @param bool $isOnline
     *
     * @return User
     */
    public function setIsOnline(bool $isOnline): ?User
    {
        $this->isOnline = $isOnline;
        return $this;
    }

    /**
     * @return bool
     */
    public function isRealUser(): ?bool
    {
        return $this->isRealUser;
    }

    /**
     * @param bool $isRealUser
     *
     * @return User
     */
    public function setIsRealUser(bool $isRealUser): ?User
    {
        $this->isRealUser = $isRealUser;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getIndex()
    {
        return $this->index;
    }

    /**
     * @param mixed $index
     *
     * @return User
     */
    public function setIndex($index)
    {
        $this->index = $index;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getIndexSelector()
    {
        return $this->indexSelector;
    }

    /**
     * @param mixed $indexSelector
     *
     * @return User
     */
    public function setIndexSelector($indexSelector)
    {
        $this->indexSelector = $indexSelector;
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function getBaseClass(): string
    {
        return \CUser::class;
    }

    /**
     * @inheritDoc
     */
    public function isSaveStatic(): bool
    {
        return false;
    }

    /**
     * @inheritDoc
     */
    public function isDeleteStatic(): bool
    {
        return true;
    }
}
