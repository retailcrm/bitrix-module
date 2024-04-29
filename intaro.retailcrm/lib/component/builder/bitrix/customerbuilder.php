<?php

/**
 * @category Integration
 * @package  Intaro\RetailCrm\Component\Builder\Bitrix
 * @author   RetailCRM <integration@retailcrm.ru>
 * @license  MIT
 * @link     http://retailcrm.ru
 * @see      http://retailcrm.ru/docs
 */

namespace Intaro\RetailCrm\Component\Builder\Bitrix;

use Intaro\RetailCrm\Component\Builder\BuilderInterface;
use Intaro\RetailCrm\Component\Converter\DateTimeConverter;
use Intaro\RetailCrm\Component\Events;
use Intaro\RetailCrm\Component\ServiceLocator;
use Intaro\RetailCrm\Service\Utils;
use Intaro\RetailCrm\Model\Api\Customer;
use Intaro\RetailCrm\Model\Bitrix\User;

/**
 * Class CustomerBuilder
 *
 * @package Intaro\RetailCrm\Component\Builder\Bitrix
 */
class CustomerBuilder implements BuilderInterface
{
    /** @var \Intaro\RetailCrm\Model\Bitrix\User */
    protected $user;

    /** @var \Intaro\RetailCrm\Model\Api\Customer */
    protected $customer;

    /** @var Utils */
    protected $utils;

    /**
     * CustomerBuilder constructor.
     */
    public function __construct()
    {
        $this->utils = ServiceLocator::get(Utils::class);
    }

    /**
     * @param \Intaro\RetailCrm\Model\Bitrix\User $user
     *
     * @return $this
     */
    public function setUser($user): self
    {
        $this->user = $user;
        return $this;
    }

    /**
     * @return \Intaro\RetailCrm\Model\Bitrix\User
     */
    public function getUser(): User
    {
        return $this->user;
    }

    /**
     * @param \Intaro\RetailCrm\Model\Api\Customer $customer
     *
     * @return CustomerBuilder
     */
    public function setCustomer(Customer $customer): CustomerBuilder
    {
        $this->customer = $customer;
        return $this;
    }

    public function build(): BuilderInterface
    {
        if (null === $this->user) {
            $this->user = new User();
        }

        $this->buildNames();
        $this->buildPhones();
        $this->buildAddress();
        $this->buildBirthdaySexAndEmail();
        $this->fillFieldsForHistoryClient();

        return $this;
    }

    /**
     * @param string $login
     * @return $this
     */
    public function setLogin($login): self
    {
        $this->user->setLogin($login);

        return $this;
    }

    /**
     * @param string $email
     * @return $this
     */
    public function setEmail($email): self
    {
        $this->user->setEmail($email);

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function reset(): BuilderInterface
    {
        $this->customer = null;
        $this->user = null;

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function getResult()
    {
        Events::push(Events::BITRIX_CUSTOMER_BUILDER_GET_RESULT, ['customer' => $this->user]);

        return $this->user;
    }

    /**
     * Fill first name, last name and second name in the user
     */
    protected function buildNames(): void
    {
        if (!empty($this->customer->firstName)) {
            $this->user->setName($this->utils->fromUTF8($this->customer->firstName));
        }

        if (!empty($this->customer->lastName)) {
            $this->user->setLastName($this->utils->fromUTF8($this->customer->lastName));
        }

        if (!empty($this->customer->patronymic)) {
            $this->user->setSecondName($this->utils->fromUTF8($this->customer->patronymic));
        }
    }

    /**
     * Fill phone numbers in the user
     */
    protected function buildPhones(): void
    {
        if (!empty($this->customer->phones)) {
            foreach ($this->customer->phones as $phone) {
                if (!empty($phone->oldNumber)) {
                    if ($this->user->getPersonalPhone() == $phone->oldNumber) {
                        $this->user->setPersonalPhone($phone->number);
                    }

                    if ($this->user->getWorkPhone() == $phone->oldNumber) {
                        $this->user->setWorkPhone($phone->number);
                    }
                }

                if (isset($phone->number)) {
                    if (strlen($this->user->getPersonalPhone()) == 0
                        && $this->user->getPersonalPhone() != $phone->number
                    ) {
                        $this->user->setPersonalPhone($phone->number);
                        continue;
                    }
                    if (strlen($this->user->getPersonalMobile()) == 0
                        && $this->user->getPersonalMobile() != $phone->number
                    ) {
                        $this->user->setPersonalMobile($phone->number);
                        continue;
                    }
                }
            }
        }
    }

    /**
     * Fill zip code and city in the user
     */
    protected function buildAddress(): void
    {
        if (!empty($this->customer->address)) {
            if (!empty($this->customer->address->index)) {
                $this->user->setPersonalZip($this->utils->fromUTF8($this->customer->address->index));
            }

            if (!empty($this->customer->address->city)) {
                $this->user->setPersonalCity($this->utils->fromUTF8($this->customer->address->city));
            }
        }
    }

    /**
     * Fill birthday, email and gender in the user
     */
    protected function buildBirthdaySexAndEmail(): void
    {
        if (!empty($this->customer->birthday)) {
            $this->user->setPersonalBirthday(DateTimeConverter::phpToBitrix($this->customer->birthday));
        }

        if (!empty($this->customer->email)) {
            $this->user->setEmail($this->utils->fromUTF8($this->customer->email));
        }

        if (!empty($this->customer->sex)) {
            $this->user->setPersonalGender($this->utils->fromUTF8($this->customer->sex));
        }
    }

    /**
     * Fill fields with placeholders in the user (only for new users from history, when some data is not provided).
     */
    protected function fillFieldsForHistoryClient(): void
    {
        if (empty($this->customer->externalId)) {
            $this->user->setPassword($this->utils->createPlaceholderPassword());
        }

        if (empty($this->customer->email) && empty($this->customer->externalId)) {
            $login = $this->utils->createPlaceholderEmail();
            $this->user->setLogin($login);
            $this->user->setEmail($login);
        }
    }
}
