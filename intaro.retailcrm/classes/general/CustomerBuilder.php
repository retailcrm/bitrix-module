<?php

IncludeModuleLangFile(__FILE__);

/**
 * Class CustomerBuilder
 */
class CustomerBuilder
{
    /** @var classes/general/Model/Customer */
    public $customer;

    /** @var classes/general/Model/CustomerAddress */
    public $customerAddress;

    /** @var array $dataCrm customerHistory */
    protected $dataCrm;

    /**
     * CustomerBuilder constructor.
     */
    public function __construct()
    {
        $this->customer = new Customer();
        $this->customerAddress = new CustomerAddress();
    }

    /**
     * @param $customer
     * @return $this
     */
    public function setCustomer($customer)
    {
        $this->customer = $customer;
        return $this;
    }

    /**
     * @return classes|Customer
     */
    public function getCustomer()
    {
        return $this->customer;
    }

    /**
     * @param $customerAddress
     * @return $this
     */
    public function setCustomerAddress($customerAddress)
    {
        $this->customerAddress = $customerAddress;
        return $this;
    }

    /**
     * @return classes|CustomerAddress
     */
    public function getCustomerAddress()
    {
        return  $this->customerAddress;
    }

    /**
     * @param $dataCrm
     * @return $this
     */
    public function setDataCrm($dataCrm)
    {
        $this->dataCrm = $dataCrm;
        return $this;
    }

    /**
     * @param $key
     * @param null $default
     * @return mixed|null
     */
    protected function getValue($key, $default = NULL)
    {
        return isset($this->dataCrm[$key]) && !empty($this->dataCrm[$key]) ?  $this->dataCrm[$key] : $default;
    }

    /**
     * @param $array
     * @param $key
     * @param null $default
     * @return mixed|null
     */
    protected function getValueArray($array, $key, $default = NULL)
    {
        return isset($this->dataCrm[$array][$key]) && !empty($this->dataCrm[$array][$key]) ?  $this->dataCrm[$array][$key] : $default;
    }

    public function build()
    {
        if (isset($this->dataCrm['deleted'])) {
            return false;
        }

        if (isset($this->dataCrm['externalId']) && !is_numeric($this->dataCrm['externalId'])) {
            unset($this->dataCrm['externalId']);
        }

        if (!isset($this->dataCrm['externalId'])) {
            $this->createCustomer();
        }

        if (isset($this->dataCrm['externalId'])) {
            $this->updateCustomer();
        }

        if (isset($this->dataCrm['address'])) {
            $this->buildAddress();
        }
    }

    public function buildAddress()
    {
       $this->customerAddress->setData($this->dataCrm['address']);
    }

    public function createCustomer()
    {
        if (!isset($this->dataCrm['id'])) {
            return false;
        }

        $registerNewUser = true;
        if (!isset($this->dataCrm['email']) || $this->dataCrm['email'] == '') {
            $login = uniqid('user_' . time()) . '@crm.com';
            $this->dataCrm['email'] = $login;
        } else {
            $dbUser = CUser::GetList(($by = 'ID'), ($sort = 'ASC'), array('=EMAIL' => $this->dataCrm['email']));
            switch ($dbUser->SelectedRowsCount()) {
                case 0:
                    $login = $this->dataCrm['email'];
                    break;
                case 1:
                    $arUser = $dbUser->Fetch();
                    $registeredUserID = $arUser['ID'];
                    $registerNewUser = false;
                    break;
                default:
                    $login = uniqid('user_' . time()) . '@crm.com';
                    break;
            }
        }

        if ($registerNewUser === true) {
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
        if (array_key_exists('firstName', $this->dataCrm)) {
            $this->customer->setName($this->dataCrm['firstName']
                ? RCrmActions::fromJSON($this->dataCrm['firstName']) : '');
        }
        if (array_key_exists('lastName', $this->dataCrm)) {
            $this->customer->setLastName($this->dataCrm['lastName']
                ? RCrmActions::fromJSON($this->dataCrm['lastName']) : '');
        }
        if (array_key_exists('patronymic', $this->dataCrm)) {
            $this->customer->setSecondName($this->dataCrm['patronymic']
                ? RCrmActions::fromJSON($this->dataCrm['patronymic']) : '');
        }

        if (isset($this->dataCrm['phones'])) {
            $user = CUser::GetList(
                ($by = "ID"),
                ($order = "desc"),
                array('ID' => $this->dataCrm['externalId']),
                array('FIELDS' => array('PERSONAL_PHONE', 'PERSONAL_MOBILE'))
            )->fetch();

            foreach ($this->dataCrm['phones'] as $phone) {
                if (isset($phone['old_number']) && in_array($phone['old_number'], $user)) {
                    $key = array_search($phone['old_number'], $user);
                    if (isset($phone['number'])) {
                        $user[$key] = $phone['number'];
                    } else {
                        $user[$key] = '';
                    }
                }

                if (isset($phone['number'])) {
                    if ((!isset($user['PERSONAL_PHONE']) || strlen($user['PERSONAL_PHONE']) == 0)
                        && $user['PERSONAL_MOBILE'] != $phone['number']
                    ) {
                        $this->customer->setPersonalPhone($phone['number']);
                        $user['PERSONAL_PHONE'] = $phone['number'];
                        continue;
                    }
                    if ((!isset($user['PERSONAL_MOBILE']) || strlen($user['PERSONAL_MOBILE']) == 0)
                        && $user['PERSONAL_PHONE'] != $phone['number']
                    ) {
                        $this->customer->setPersonalMobile($phone['number']);
                        $user['PERSONAL_MOBILE'] = $phone['number'];
                        continue;
                    }
                }
            }
        }

        if (array_key_exists('index', $this->dataCrm['address'])) {
            $this->customer->setPersonalZip($this->dataCrm['address']['index']
                ? RCrmActions::fromJSON($this->dataCrm['address']['index']) : '');
        }
        if (array_key_exists('city', $this->dataCrm['address'])) {
            $this->customer->setPersonalCity($this->dataCrm['address']['city']
                ? RCrmActions::fromJSON($this->dataCrm['address']['city']) : '');
        }

        if (array_key_exists('birthday', $this->dataCrm)) {
            $this->customer->setPersonalBirthday(date("d.m.Y", strtotime($this->dataCrm['birthday'])));
        }

        if (array_key_exists('email', $this->dataCrm)) {
            $this->customer->setEmail($this->dataCrm['email'] ? RCrmActions::fromJSON($this->dataCrm['email']) : '');
        }

        if (array_key_exists('sex', $this->dataCrm)) {
            $this->customer->setPersonalGender($this->dataCrm['sex'] ? RCrmActions::fromJSON($this->dataCrm['sex']) : '');
        }
    }

    /**
     * @param array $array
     * @param array $symbols
     * @return array
     */
    function arrayClear(array $array, array $symbols = array('', 0, null))
    {
        return array_diff($array, $symbols);
    }

    /**
     * @param $data
     * @return array
     */
    function objectToArray($data)
    {
        return $this->arrayClear(json_decode(json_encode($data), true));
    }
}
