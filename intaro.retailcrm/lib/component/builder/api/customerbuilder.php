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

use DateTime;
use Intaro\RetailCrm\Component\Builder\Exception\BuilderException;
use Intaro\RetailCrm\Service\CookieService;
use Intaro\RetailCrm\Component\ConfigProvider;
use Intaro\RetailCrm\Component\Converter\DateTimeConverter;
use Intaro\RetailCrm\Component\Events;
use Intaro\RetailCrm\Component\ServiceLocator;
use Intaro\RetailCrm\Model\Api\Address;
use Intaro\RetailCrm\Model\Api\Contragent;
use Intaro\RetailCrm\Model\Api\Customer;
use Intaro\RetailCrm\Model\Api\Phone;
use Intaro\RetailCrm\Model\Bitrix\User;
use Intaro\RetailCrm\Component\Builder\BuilderInterface;

/**
 * Class CustomerBuilder
 *
 * @package Intaro\RetailCrm\Component\Builder\Api
 */
class CustomerBuilder implements BuilderInterface
{
    /** @var \Intaro\RetailCrm\Model\Bitrix\User $user */
    private $user;

    /** @var \Intaro\RetailCrm\Model\Api\Customer $customer */
    private $customer;

    /** @var CookieService */
    private $cookieExtractor;

    /** @var string $personTypeId */
    private $personTypeId;

    /** @var bool */
    private $attachDaemonCollectorId = false;

    /** @var array */
    private $customFields;

    /**
     * CustomerBuilder constructor.
     */
    public function __construct()
    {
        $this->cookieExtractor = ServiceLocator::get(CookieService::class);
    }

    /**
     * @inheritDoc
     */
    public function build(): BuilderInterface
    {
        $this->buildBase(
            ConfigProvider::getContragentTypeForPersonType($this->personTypeId ?? '')
            ?? 'individual'
        );
        $this->buildNames();
        $this->buildPhones();
        $this->buildAddress();

        if ($this->attachDaemonCollectorId) {
            $this->buildDaemonCollectorId();
        }
        $this->handleFields();
        $this->customer->customFields = $this->customFields;

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function reset(): BuilderInterface
    {
        $this->user = null;
        $this->customer = null;
        $this->personTypeId = null;

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function getResult()
    {
        Events::push(Events::API_CUSTOMER_BUILDER_GET_RESULT, ['customer' => $this->customer]);

        return $this->customer;
    }

    /**
     * @param \Intaro\RetailCrm\Model\Bitrix\User $user
     *
     * @return CustomerBuilder
     */
    public function setUser(User $user): CustomerBuilder
    {
        $this->user = $user;
        return $this;
    }

    /**
     * @param string $personTypeId
     *
     * @return CustomerBuilder
     */
    public function setPersonTypeId(string $personTypeId): CustomerBuilder
    {
        $this->personTypeId = $personTypeId;
        return $this;
    }

    /**
     * @param bool $attachDaemonCollectorId
     *
     * @return CustomerBuilder
     */
    public function setAttachDaemonCollectorId(bool $attachDaemonCollectorId): CustomerBuilder
    {
        $this->attachDaemonCollectorId = $attachDaemonCollectorId;
        return $this;
    }

    /**
     * @param array $customFields
     *
     * @return $this
     */
    public function setCustomFields(array $customFields): CustomerBuilder
    {
        $this->customFields = $customFields;

        return $this;
    }

    /**
     * Create base customer with initial data.
     *
     * @param string $contragentType
     */
    protected function buildBase(string $contragentType): void
    {
        $this->customer = new Customer();
        $this->customer->contragent = new Contragent();
        $this->customer->contragent->contragentType = $contragentType;
        $this->customer->externalId = $this->user->getId();
        $this->customer->email = $this->user->getEmail();
        $this->customer->createdAt = $this->user->getDateRegister();
        $this->customer->subscribed = !empty($this->user->getSubscribe());
    }

    /**
     * Build names.
     */
    protected function buildNames(): void
    {
        $this->customer->firstName = $this->user->getName();
        $this->customer->lastName = $this->user->getLastName();
        $this->customer->patronymic = $this->user->getSecondName();
    }

    /**
     * Build phones.
     */
    protected function buildPhones(): void
    {
        $this->customer->phones = [];

        if (!empty($this->user->getPersonalPhone())) {
            $this->addPhone($this->user->getPersonalPhone());
        }

        if (!empty($this->user->getWorkPhone())) {
            $this->addPhone($this->user->getWorkPhone());
        }
    }

    /**
     * Build address.
     */
    protected function buildAddress(): void
    {
        $address = new Address();

        if (!empty($this->user->getPersonalCity())) {
            $address->city = $this->user->getPersonalCity();
        }

        if (!empty($this->user->getPersonalStreet())) {
            $address->text = $this->user->getPersonalStreet();
        }

        if (!empty($this->user->getPersonalZip())) {
            $address->index = $this->user->getPersonalZip();
        }

        $this->customer->address = $address;
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

    /**
     * @param string $number
     */
    protected function addPhone(string $number): void
    {
        $phone = new Phone();
        $phone->number = $number;
        $this->customer->phones[] = $phone;
    }


    private function handleFields(): void
    {
        if (is_array($this->customFields) && count($this->customFields) === 0) {
            return;
        }

        foreach ($this->customFields as $key => $fieldValue) {
            if ($key === 'lastName') {
                $this->customer->lastName = $fieldValue;
                unset($this->customFields[$key]);
                continue;
            }

            if ($key === 'email') {
                $this->customer->email =$fieldValue;
                unset($this->customFields[$key]);
                continue;
            }

            if ($key === 'firstName') {
                $this->customer->firstName = $fieldValue;
                unset($this->customFields[$key]);
                continue;
            }

            if ($key === 'PERSONAL_PHONE' || $key === 'phoneNumber') {
                $this->addPhone(htmlspecialchars(trim($fieldValue)));
                unset($this->customFields[$key]);
                continue;
            }
        }
    }
}
