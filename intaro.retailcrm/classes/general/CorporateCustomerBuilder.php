<?php
/**
 * Class CorporateCustomerBuilder
 */
class CorporateCustomerBuilder extends AbstractBuilder implements RetailcrmBuilderInterface
{
    /** @var Customer */
    protected $customer;

    /**@var CustomerBuilder */
    protected $customerBuilder;

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
        $this->customerBuilder = new CustomerBuilder();
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
     * @param object $customerBuilder
     * @return $this
     */
    public function setCustomerBuilder($customerBuilder)
    {
        $this->$customerBuilder = $customerBuilder;
        return $this;
    }

    /**
     * @return object|CustomerBuilder
     */
    public function getCustomerBuilder()
    {
        return $this->customerBuilder;
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

    /**
     * @return object|BuyerProfile
     */
    public function getBuyerProfile()
    {
        return $this->buyerProfile;
    }

    /**
     * @param array $contragentTypes
     * @return $this
     */
    public function setContragentTypes($contragentTypes)
    {
        $this->contragentTypes = $contragentTypes;

        return $this;
    }

    public function build()
    {
        if (isset($this->dataCrm['contact'])) {
            $this->customerBuilder->setDataCrm($this->dataCrm['contact'])->build();
            $this->corporateContact = $this->customerBuilder->getCustomer();
            $this->customer = $this->customerBuilder->getCustomer();
        } else {
            $this->corporateContact = null;
            $this->customer = null;
        }

        if (isset($this->dataCrm['company']['address'])) {
            $this->buildAddress();
        }

        if (isset($this->dataCrm['company'])) {
            $this->buildBuyerProfile();
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
