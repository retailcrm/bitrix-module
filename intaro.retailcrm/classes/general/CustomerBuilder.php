<?php

/**
 * Class CustomerBuilder
 */
class CustomerBuilder extends AbstractBuilder implements RetailcrmBuilderInterface
{
    /** @var Customer */
    protected $customer;

    /** @var CustomerAddress */
    protected $customerAddress;

    /** @var array $dataCrm customerHistory */
    protected $dataCrm;

    /** @var AddressBuilder */
    protected $addressBuilder;

    /** @var CUser */
    protected $user;

    /** @var bool $registerNewUser */
    protected $registerNewUser;

    /** @var int $registeredUserID */
    protected $registeredUserID;

    /**
     * CustomerBuilder constructor.
     */
    public function __construct()
    {
        $this->customer = new Customer();
        $this->customerAddress = new CustomerAddress();
        $this->addressBuilder = new AddressBuilder();
    }
    
    /**
     * @param Customer $customer
     * @return $this
     */
    public function setCustomer(Customer $customer): CustomerBuilder
    {
        $this->customer = $customer;
        
        return $this;
    }

    /**
     * @return Customer
     */
    public function getCustomer(): Customer
    {
        return $this->customer;
    }

    /**
     * @param CustomerAddress $customerAddress
     * @return $this
     */
    public function setCustomerAddress($customerAddress): CustomerBuilder
    {
        $this->customerAddress = $customerAddress;
        return $this;
    }

    /**
     * @return CustomerAddress
     */
    public function getCustomerAddress(): CustomerAddress
    {
        return  $this->customerAddress;
    }

    /**
     * @param array $dataCrm
     * @return $this
     */
    public function setDataCrm($dataCrm): CustomerBuilder
    {
        $this->dataCrm = $dataCrm;
        
        return $this;
    }

    /**
     * @param array $user
     * @return $this
     */
    public function setUser($user): CustomerBuilder
    {
        $this->user = $user;
        
        return $this;
    }
    
    /**
     * @param int $registeredUserID
     * @return $this
     */
    public function setRegisteredUserID(int $registeredUserID): CustomerBuilder
    {
        $this->registeredUserID = $registeredUserID;
        
        return $this;
    }

    /**
     * @return int
     */
    public function getRegisteredUserID(): int
    {
        return $this->registeredUserID;
    }

    /**
     * @return bool
     */
    public function getRegisterNewUser(): bool
    {
        return $this->registerNewUser;
    }

    public function build()
    {
        if (!empty($this->dataCrm['firstName'])) {
            $this->customer->setName($this->fromJSON($this->dataCrm['firstName']));
        }

        if (!empty($this->dataCrm['lastName'])) {
            $this->customer->setLastName($this->fromJSON($this->dataCrm['lastName']));
        }

        if (!empty($this->dataCrm['patronymic'])) {
            $this->customer->setSecondName($this->fromJSON($this->dataCrm['patronymic']));
        }

        if (isset($this->dataCrm['phones'])) {
            foreach ($this->dataCrm['phones'] as $phone) {
                if (isset($phone['old_number']) && in_array($phone['old_number'], $this->user, true)) {
                    $key = array_search($phone['old_number'], $this->user, true);

                    if (isset($phone['number'])) {
                        $this->user[$key] = $phone['number'];
                    } else {
                        $this->user[$key] = '';
                    }
                }

                if (isset($phone['number'])) {
                    if ((!isset($this->user['PERSONAL_PHONE']) || '' == $this->user['PERSONAL_PHONE'])
                        && $this->user['PERSONAL_MOBILE'] != $phone['number']
                    ) {
                        $this->customer->setPersonalPhone($phone['number']);
                        $this->user['PERSONAL_PHONE'] = $phone['number'];
                        continue;
                    }
                    if ((!isset($this->user['PERSONAL_MOBILE']) || '' == $this->user['PERSONAL_MOBILE'])
                        && $this->user['PERSONAL_PHONE'] != $phone['number']
                    ) {
                        $this->customer->setPersonalMobile($phone['number']);
                        $this->user['PERSONAL_MOBILE'] = $phone['number'];
                        continue;
                    }
                }
            }
        }

        if (!empty($this->dataCrm['address']['index'])) {
            $this->customer->setPersonalZip($this->fromJSON($this->dataCrm['address']['index']));
        }

        if (!empty($this->dataCrm['address']['city'])) {
            $this->customer->setPersonalCity($this->fromJSON($this->dataCrm['address']['city']));
        }

        if (!empty($this->dataCrm['birthday'])) {
            $this->customer->setPersonalBirthday($this->fromJSON(
                date("d.m.Y", strtotime($this->dataCrm['birthday']))
            ));
        }

        if (!empty($this->dataCrm['email'])) {
            $this->customer->setEmail($this->fromJSON($this->dataCrm['email']));
        }

        if (!empty($this->dataCrm['sex'])) {
            $this->customer->setPersonalGender($this->fromJSON($this->dataCrm['sex']));
        }

        if (empty($this->dataCrm['externalId'])) {
            $userPassword = uniqid("R");
            $this->customer->setPassword($userPassword)
                ->setConfirmPassword($userPassword);
        }

        if ((!isset($this->dataCrm['email']) || $this->dataCrm['email'] == '')
            && (!isset($this->dataCrm['externalId']))
        ) {
            $login = uniqid('user_' . time()) . '@example.com';
            $this->customer->setLogin($login)
                ->setEmail($login);
        }

        if (isset($this->dataCrm['address'])) {
            $this->buildAddress();
        }
    }

    public function buildAddress()
    {
        if (isset($this->dataCrm['address'])) {
            $this->addressBuilder->setDataCrm($this->dataCrm['address'])->build();
            $this->customerAddress = $this->addressBuilder->getCustomerAddress();
        } else {
            $this->customerAddress = null;
        }
    }
    
    /**
     * @param string $login
     * @return $this
     */
    public function setLogin(string $login): CustomerBuilder
    {
        $this->customer->setLogin($login);

        return $this;
    }
    
    /**
     * @param string $email
     * @return $this
     */
    public function setEmail(string $email): CustomerBuilder
    {
        $this->customer->setEmail($email);

        return $this;
    }
    
    public function reset(): void
    {
        $this->customer = new Customer();
        $this->customerAddress = new CustomerAddress();
        $this->addressBuilder->reset();
    }
}
