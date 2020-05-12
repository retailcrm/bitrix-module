<?php
/**
 * Class CorporateCustomerBuilder
 */
class CorporateCustomerBuilder extends BuilderBase implements RetailcrmBuilderInterface
{
    /** @var Customer */
    protected $customer;

    /** @var CustomerAddress */
    protected $customerAddress;

    /** @var array $dataCrm customerHistory */
    protected $dataCrm;

    /** @var array $corporateContact */
    protected $corporateContact;

    /** @var int $orderCustomerExtId */
    protected $orderCustomerExtId;

    /** @var BuyerProfile */
    public $buyerProfile;

    /** @var CUser */
    protected $dbUser;

    /** @var bool $registerNewUser */
    protected $registerNewUser;

    /** @var int $registeredUserID */
    protected $registeredUserID;

    /**@var AddressBuilder */
    protected $addressBuilder;

    /**@var array $contragentTypes */
    protected $contragentTypes;

    /**
     * CorporateCustomerBuilder constructor.
     */
    public function __construct()
    {
        $this->customer = new Customer();
        $this->customerAddress = new CustomerAddress();
        $this->buyerProfile = new BuyerProfile();
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
     * @param int $registeredUserID
     * @return $this
     */
    public function setRegisteredUserID($registeredUserID)
    {
        $this->registeredUserID = $registeredUserID;
        return $this;
    }

    /**
     * @return bool
     */
    public function getRegisterNewUser()
    {
        return $this->registerNewUser;
    }

    /**
     * @return int
     */
    public function getRegisteredUserID()
    {
        return $this->registeredUserID;
    }

    /**
     * @param int $data
     * @return $this
     */
    public function setOrderCustomerExtId($data)
    {
        $this->orderCustomerExtId = $data;

        return $this;
    }

    /**
     * @return int
     */
    public function getOrderCustomerExtId()
    {
        return $this->orderCustomerExtId;
    }

    /**
     * @param array $data
     * @return $this
     */
    public function setCorporateContact($data)
    {
        $this->corporateContact = $data;

        return $this;
    }

    /**
     * @return array
     */
    public function getCorporateContact()
    {
        return $this->corporateContact;
    }

    public function build()
    {
        if (!isset($this->dataCrm['externalId'])) {
            $this->buildCustomer();
            $this->buildBuyerProfile();
        }

        if (isset($this->dataCrm['company']['address'])) {
            $this->buildAddress();
        }
    }

    public function buildCustomer()
    {
        if (empty($this->orderCustomerExtId)) {
            if (!isset($this->dataCrm['customer']['id'])
                || (RetailCrmOrder::isOrderCorporate($this->dataCrm)
                    && (!isset($this->dataCrm['contact']['id']) || !isset($this->dataCrm['customer']['id'])))
            ) {
                return false;
            }

            $login = null;
            $this->registerNewUser = true;

            if (!isset($this->dataCrm['customer']['email']) || empty($this->dataCrm['customer']['email'])) {
                if (RetailCrmOrder::isOrderCorporate($this->dataCrm) && !empty($this->corporateContact['email'])) {
                    $login = $this->corporateContact['email'];
                    $this->dataCrm['customer']['email'] = $this->corporateContact['email'];
                } else {
                    $login = uniqid('user_' . time()) . '@crm.com';
                    $this->dataCrm['customer']['email'] = $login;
                }
            }
            if (isset($this->dbUser)) {
                switch ($this->dbUser->SelectedRowsCount()) {
                    case 0:
                        $login = $this->dataCrm['customer']['email'];
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

            if ( $this->registerNewUser === true) {
                $userPassword = uniqid("R");
                $userData = RetailCrmOrder::isOrderCorporate($this->dataCrm)
                    ? $this->corporateContact
                    : $this->dataCrm['customer'];

                $this->customer->setName(RCrmActions::fromJSON($userData['firstName']))
                    ->setLastName(RCrmActions::fromJSON($userData['lastName']))
                    ->setSecondName(RCrmActions::fromJSON($userData['patronymic']))
                    ->setEmail($this->dataCrm['customer']['email'])
                    ->setLogin($login)
                    ->setActive("Y")
                    ->setPassword($userPassword)
                    ->setConfirmPassword($userPassword);

                if (!empty($userData['phones'][0])) {
                    $this->customer->setPersonalPhone($userData['phones'][0]);
                }

                if (!empty($userData['phones'][1])) {
                    $this->customer->setPersonalMobile($userData['phones'][1]);
                }
            }
        }
    }

    public function buildBuyerProfile()
    {
        if (RetailCrmOrder::isOrderCorporate($this->dataCrm) && !empty($this->dataCrm['company'])) {
            $this->buyerProfile->setName($this->dataCrm['company']['name'])
                ->setUserId($this->dataCrm['contact']['externalId'])
                ->setPersonTypeId($this->contragentTypes['legal-entity']);
        }
    }

    public function buildAddress()
    {
        if (isset($this->dataCrm['company']['address'])) {
            $this->addressBuilder->setDataCrm($this->dataCrm['company']['address'])->build();
            $this->customerAddress = $this->addressBuilder->getCustomerAddress();
        } else {
            $this->customerAddress = null;
        }
    }
}
