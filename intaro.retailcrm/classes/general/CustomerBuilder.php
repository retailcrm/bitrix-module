<?php

/**
 * Class CustomerBuilder
 */
class CustomerBuilder extends BuilderBase implements RetailcrmBuilderInterface
{
    /** @var Customer */
    public $customer;

    /** @var CustomerAddress */
    public $customerAddress;

    /** @var array $dataCrm customerHistory */
    protected $dataCrm;

    /** @var AddressBuilder */
    public $addressBuilder;

    /** @var CUser */
    public $dbUser;

    /** @var CUser */
    public $user;

    /** @var bool $registerNewUser */
    public $registerNewUser;

    /** @var int $registeredUserID */
    public $registeredUserID;

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
     * @param object $customer
     * @return $this
     */
    public function setCustomer($customer)
    {
        $this->customer = $customer;
        return $this;
    }

    /**
     * @return object|Customer
     */
    public function getCustomer()
    {
        return $this->customer;
    }

    /**
     * @param object $customerAddress
     * @return $this
     */
    public function setCustomerAddress($customerAddress)
    {
        $this->customerAddress = $customerAddress;
        return $this;
    }

    /**
     * @return object|CustomerAddress
     */
    public function getCustomerAddress()
    {
        return  $this->customerAddress;
    }

    /**
     * @param array $dataCrm
     * @return $this
     */
    public function setDataCrm($dataCrm)
    {
        $this->dataCrm = $dataCrm;
        return $this;
    }

    /**
     * @param object $dbUser
     * @return $this
     */
    public function setDbUser($dbUser)
    {
        $this->dbUser = $dbUser;
        return $this;
    }

    /**
     * @param object $user
     * @return $this
     */
    public function setUser($user)
    {
        $this->user = $user;
        return $this;
    }

    /**
     * @param int $registeredUserID
     * @return $this
     */
    public function setRegisteredUserID($registeredUserID)
    {
        $this->registeredUserID = $registeredUserID;
        return $this;
    }

    /**
     * @param string $key
     * @param mixed $default
     * @return mixed|null
     */
    protected function getValue($key, $default = NULL)
    {
        return isset($this->dataCrm[$key]) && !empty($this->dataCrm[$key]) ?  $this->dataCrm[$key] : $default;
    }

    /**
     * @param array $array
     * @param string $key
     * @param mixed $default
     * @return mixed|null
     */
    protected function getValueArray($array, $key, $default = NULL)
    {
        return isset($this->dataCrm[$array][$key]) && !empty($this->dataCrm[$array][$key]) ?  $this->dataCrm[$array][$key] : $default;
    }

    public function build()
    {
        if (isset($this->dataCrm['deleted'])) {
            return $this;
        }

        if (isset($this->dataCrm['externalId']) && !is_numeric($this->dataCrm['externalId'])) {
            unset($this->dataCrm['externalId']);
        }

        if (!isset($this->dataCrm['externalId'])) {
            $this->createCustomer();
        }

        if (isset($this->registeredUserID) || isset($this->dataCrm['externalId'])) {
            $this->updateCustomer();
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

    public function createCustomer()
    {
        if (!isset($this->dataCrm['id'])) {
            return $this;
        }

        $this->registerNewUser = true;
        if (!isset($this->dataCrm['email']) || $this->dataCrm['email'] == '') {
            $login = uniqid('user_' . time()) . '@crm.com';
            $this->dataCrm['email'] = $login;
        } else {
            if (isset($this->dbUser)) {
                switch ($this->dbUser->SelectedRowsCount()) {
                    case 0:
                        $login = $this->dataCrm['email'];
                        break;
                    case 1:
                        $arUser = $this->dbUser->Fetch();
                        $this->setRegisteredUserID($arUser['ID']);
                        $this->registerNewUser = false;
                        break;
                    default:
                        $login = uniqid('user_' . time()) . '@crm.com';
                        break;
                }
            }
        }

        if ( $this->registerNewUser === true) {
            $userPassword = uniqid("R");

            $this->customer->setEmail($this->dataCrm['email'])
                ->setLogin($login)
                ->setActive("Y")
                ->setPassword($userPassword)
                ->setConfirmPassword($userPassword);
        }
    }

    public function updateCustomer()
    {
        if (!empty($this->dataCrm['firstName'])) {
            $this->customer->setName(RCrmActions::fromJSON($this->dataCrm['firstName']));
        }

        if (!empty($this->dataCrm['lastName'])) {
            $this->customer->setLastName(RCrmActions::fromJSON($this->dataCrm['lastName']));
        }

        if (!empty($this->dataCrm['patronymic'])) {
            $this->customer->setSecondName(RCrmActions::fromJSON($this->dataCrm['patronymic']));
        }

        if (isset($this->dataCrm['phones'])) {
            foreach ($this->dataCrm['phones'] as $phone) {
                if (isset($phone['old_number']) && in_array($phone['old_number'], $this->user)) {
                    $key = array_search($phone['old_number'], $this->user);
                    if (isset($phone['number'])) {
                        $this->user[$key] = $phone['number'];
                    } else {
                        $this->user[$key] = '';
                    }
                }

                if (isset($phone['number'])) {
                    if ((!isset($this->user['PERSONAL_PHONE']) || strlen($this->user['PERSONAL_PHONE']) == 0)
                        && $this->user['PERSONAL_MOBILE'] != $phone['number']
                    ) {
                        $this->customer->setPersonalPhone($phone['number']);
                        $this->user['PERSONAL_PHONE'] = $phone['number'];
                        continue;
                    }
                    if ((!isset($this->user['PERSONAL_MOBILE']) || strlen($this->user['PERSONAL_MOBILE']) == 0)
                        && $this->user['PERSONAL_PHONE'] != $phone['number']
                    ) {
                        $this->customer->setPersonalMobile($phone['number']);
                        $this->user['PERSONAL_MOBILE'] = $phone['number'];
                        continue;
                    }
                }
            }
        }

        if (!empty($this->dataCrm['index'])) {
            $this->customer->setPersonalZip(RCrmActions::fromJSON($this->dataCrm['index']));
        }

        if (!empty($this->dataCrm['city'])) {
            $this->customer->setPersonalCity(RCrmActions::fromJSON($this->dataCrm['city']));
        }

        if (!empty($this->dataCrm['birthday'])) {
            $this->customer->setPersonalBirthday(RCrmActions::fromJSON($this->dataCrm['birthday']));
        }

        if (!empty($this->dataCrm['email'])) {
            $this->customer->setEmail(RCrmActions::fromJSON($this->dataCrm['email']));
        }

        if (!empty($this->dataCrm['sex'])) {
            $this->customer->setPersonalGender(RCrmActions::fromJSON($this->dataCrm['sex']));
        }
    }
}
