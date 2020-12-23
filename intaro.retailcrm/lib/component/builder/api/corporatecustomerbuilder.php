<?php
/**
 * PHP version 7.1
 *
 * @category Integration
 * @package  Intaro\RetailCrm\Component\Builder\API
 * @author   RetailCRM <integration@retailcrm.ru>
 * @license  MIT
 * @link     http://retailcrm.ru
 * @see      http://retailcrm.ru/docs
 */
namespace Intaro\RetailCrm\Component\Builder\Api;

use Bitrix\Main\Type\DateTime;
use Bitrix\Sale\Order;
use Intaro\RetailCrm\Component\Builder\BuilderInterface;
use Intaro\RetailCrm\Component\Builder\Exception\BuilderException;
use Intaro\RetailCrm\Service\CollectorCookieExtractor;
use Intaro\RetailCrm\Component\ConfigProvider;
use Intaro\RetailCrm\Component\Converter\DateTimeConverter;
use Intaro\RetailCrm\Component\Events;
use Intaro\RetailCrm\Component\ServiceLocator;
use Intaro\RetailCrm\Model\Api\Address;
use Intaro\RetailCrm\Model\Api\Company;
use Intaro\RetailCrm\Model\Api\Contragent;
use Intaro\RetailCrm\Model\Api\Customer;
use Intaro\RetailCrm\Model\Api\CustomerContact;
use Intaro\RetailCrm\Model\Bitrix\User;
use Intaro\RetailCrm\Repository\UserRepository;

/**
 * Class CorporateCustomerBuilder
 *
 *TODO
 * Support for building corporate customers is partial for now. Full support should be implemented, which would be
 * possible with a full refactoring. For current purposes this will work.
 *
 * @package Intaro\RetailCrm\Component\Builder\Api
 */
class CorporateCustomerBuilder implements BuilderInterface
{
    /** @var \Intaro\RetailCrm\Model\Bitrix\User $user */
    private $user;

    /** @var \Intaro\RetailCrm\Model\Api\Customer $customer */
    private $customer;

    /** @var \Bitrix\Sale\Order $order */
    private $order;

    /** @var CollectorCookieExtractor */
    private $cookieExtractor;

    /** @var array */
    private $sites;

    /** @var array */
    private $legalDetails;

    /** @var array */
    private $contragentTypes;

    /** @var string */
    private $foundAddress;

    /** @var bool */
    private $buildChildEntities = false;

    /** @var bool */
    private $mainCompany = false;

    /** @var bool */
    private $mainContact = false;

    /** @var bool */
    private $mainAddress = false;

    /** @var bool */
    private $attachDaemonCollectorId = false;

    /**
     * CorporateCustomerBuilder constructor.
     */
    public function __construct()
    {
        $this->cookieExtractor = ServiceLocator::get(CollectorCookieExtractor::class);
        $this->sites = ConfigProvider::getSitesList();
        $this->legalDetails = ConfigProvider::getLegalDetails();
        $this->contragentTypes = ConfigProvider::getContragentTypes();
    }

    /**
     * @return $this|\Intaro\RetailCrm\Component\Builder\BuilderInterface
     * @throws \Bitrix\Main\ArgumentException
     * @throws \Bitrix\Main\NotImplementedException
     * @throws \Bitrix\Main\ObjectPropertyException
     * @throws \Bitrix\Main\SystemException
     * @throws \Intaro\RetailCrm\Component\Builder\Exception\BuilderException
     */
    public function build(): BuilderInterface
    {
        if (!($this->order instanceof Order)) {
            throw new BuilderException('Order should be provided for building corporate customer!');
        }

        $contragentType = ConfigProvider::getContragentTypeForPersonType($this->order->getPersonTypeId());

        if (null === $contragentType) {
            throw new BuilderException(sprintf(
                'Cannot find corresponding contragent type for PERSON_TYPE_ID `%s`',
                $this->order->getPersonTypeId()
            ));
        }

        $this->customer = new Customer();
        $dateCreated = $this->order->getDateInsert();

        if ($dateCreated instanceof DateTime) {
            $this->customer->createdAt = DateTimeConverter::bitrixToPhp($dateCreated);
        }

        $this->buildLegalDetails();

        if ($this->buildChildEntities) {
            $this->buildCustomerContact();
            $this->buildCustomerCompany();
            $this->buildCustomerAddresses();
        }

        if ($this->attachDaemonCollectorId) {
            $this->buildDaemonCollectorId();
        }

        return $this;
    }

    /**
     * @param bool $buildChildEntities
     *
     * @return CorporateCustomerBuilder
     */
    public function setBuildChildEntities(bool $buildChildEntities): CorporateCustomerBuilder
    {
        $this->buildChildEntities = $buildChildEntities;
        return $this;
    }

    /**
     * @param bool $mainCompany
     *
     * @return CorporateCustomerBuilder
     */
    public function setMainCompany(bool $mainCompany): CorporateCustomerBuilder
    {
        $this->mainCompany = $mainCompany;
        return $this;
    }

    /**
     * @param bool $mainContact
     *
     * @return CorporateCustomerBuilder
     */
    public function setMainContact(bool $mainContact): CorporateCustomerBuilder
    {
        $this->mainContact = $mainContact;
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function reset(): BuilderInterface
    {
        $this->user = null;
        $this->customer = null;
        $this->order = null;
        $this->foundAddress = null;
        $this->buildChildEntities = false;
        $this->mainAddress = false;
        $this->mainCompany = false;
        $this->mainContact = false;
        $this->attachDaemonCollectorId = false;

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function getResult()
    {
        Events::push(Events::API_CORPORATE_CUSTOMER_BUILDER_GET_RESULT, ['customer' => $this->customer]);

        return $this->customer;
    }

    /**
     * @param \Intaro\RetailCrm\Model\Bitrix\User $user
     *
     * @return CorporateCustomerBuilder
     */
    public function setUser(User $user): CorporateCustomerBuilder
    {
        $this->user = $user;
        return $this;
    }

    /**
     * @param \Bitrix\Sale\Order $order
     *
     * @return CorporateCustomerBuilder
     * @throws \Bitrix\Main\ArgumentException
     * @throws \Bitrix\Main\ObjectPropertyException
     * @throws \Bitrix\Main\SystemException
     * @throws \Intaro\RetailCrm\Component\Builder\Exception\BuilderException
     */
    public function setOrder(Order $order): CorporateCustomerBuilder
    {
        $this->order = $order;
        $userId = $order->getUserId();

        if (null === $userId || !is_int($userId)) {
            throw new BuilderException('Either user in order is not set or user id is not valid.');
        }

        $this->user = UserRepository::getById($userId);
        return $this;
    }

    /**
     * @param bool $attachDaemonCollectorId
     *
     * @return CorporateCustomerBuilder
     */
    public function setAttachDaemonCollectorId(bool $attachDaemonCollectorId): CorporateCustomerBuilder
    {
        $this->attachDaemonCollectorId = $attachDaemonCollectorId;
        return $this;
    }

    /**
     * @throws \Bitrix\Main\ArgumentException
     * @throws \Bitrix\Main\NotImplementedException
     * @throws \Bitrix\Main\ObjectPropertyException
     * @throws \Bitrix\Main\SystemException
     */
    protected function buildLegalDetails(): void
    {
        $this->customer->contragent = new Contragent();

        /** @var \Bitrix\Sale\PropertyValue $property */
        foreach ($this->order->getPropertyCollection() as $property) {
            if (ConfigProvider::getCorporateClientName() === $property->getField('CODE')) {
                $this->customer->nickName = $property->getValue();
            }

            if (ConfigProvider::getCorporateClientAddress() === $property->getField('CODE')) {
                $this->foundAddress = $property->getValue();
            }

            if (!empty($this->legalDetails)) {
                $contragentProperty = array_search(
                    $property->getField('CODE'),
                    $this->legalDetails[$this->order->getPersonTypeId()],
                    false
                );

                if (property_exists(Contragent::class, $contragentProperty)) {
                    $this->customer->contragent->$contragentProperty = $property->getValue();
                }
            }
        }

        if (array_key_exists($this->order->getPersonTypeId(), $this->contragentTypes)) {
            $this->customer->contragent->contragentType = $this->contragentTypes[$this->order->getPersonTypeId()];
        }

        if (empty($this->customer->nickName)) {
            $this->customer->nickName = $this->user->getWorkCompany();
        }
    }

    /**
     * @throws \Intaro\RetailCrm\Component\Builder\Exception\BuilderException
     */
    protected function buildCustomerContact(): void
    {
        $site = null;
        $shop = $this->order->getField('LID');

        if (array_key_exists($shop, $this->sites) &&  !empty($this->sites[$shop])) {
            $site = $this->sites[$shop];
        }

        if (null === $site) {
            throw new BuilderException(sprintf(
                'Site `%s` is not connected to any sites in the RetailCRM',
                $shop
            ));
        }

        $contact = new CustomerContact();
        $contact->isMain = $this->mainContact;
        $contact->customer = new Customer();
        $contact->customer->externalId = $this->user->getId();
        $contact->customer->site = $site;

        $this->customer->customerContacts = [$contact];
    }

    /**
     * Builds customer company
     */
    protected function buildCustomerCompany(): void
    {
        $company = new Company();
        $company->name = $this->customer->nickName;
        $company->isMain = $this->mainCompany;

        $this->customer->companies = [$company];
    }

    /**
     * Builds customer addresses
     */
    protected function buildCustomerAddresses(): void
    {
        if (!empty($this->foundAddress)) {
            $address = new Address();
            $address->name = $this->customer->nickName;
            $address->text = $this->foundAddress;
            $address->isMain = $this->mainAddress;

            $this->customer->addresses = [$address];
        }
    }

    /**
     * Integrated Daemon Collector cookie (if it's present).
     */
    protected function buildDaemonCollectorId(): void
    {
        if ($this->cookieExtractor->extractCookie()) {
            $this->customer->browserId = $this->cookieExtractor->extractCookie();
        }
    }
}
