<?php

IncludeModuleLangFile(__FILE__);

/**
 * Class CustomerCorpBuilder
 */
class CustomerCorpBuilder implements RetailcrmBuilderInterface
{
    /** @var classes/general/Model/Customer */
    public $customer;

    /** @var classes/general/Model/CustomerAddress */
    public $customerAddress;

    /** @var array $dataCrm customerHistory */
    protected $dataCrm;

    public $corporateContact;
    public $orderCustomerExtId;

    /** @var classes/general/Model/BuyerProfile */
    public $buyerProfile;

    protected $api;
    public $dbUser;

    /**
     * CustomerCorpBuilder constructor.
     * @param $api
     */
    public function __construct($api)
    {
        $this->api = $api;
        $this->customer = new Customer();
        $this->customerAddress = new CustomerAddress();
        $this->buyerProfile = new BuyerProfile();
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
     * @param $dbUser
     * @return $this
     */
    public function setDbUser($dbUser)
    {
        $this->dbUser = $dbUser;
        return $this;
    }

    public function build()
    {
        if (RetailCrmOrder::isOrderCorporate($this->dataCrm)) {
           $this->getCorporateContact();
        }

        if (!isset($this->dataCrm['externalId'])) {
            $this->buildCustomer();
            $this->buildBuyerProfile();
        }

        if (isset($this->dataCrm['company']['address'])) {
            $this->buildAddress();
        }
    }

    public function getCorporateContact()
    {
        if (!empty($this->dataCrm['contact'])) {
            if (isset($this->dataCrm['contact']['email'])) {
                $this->corporateContact = $this->dataCrm['contact'];
                $this->orderCustomerExtId = isset($this->corporateContact['externalId'])
                    ? $this->corporateContact['externalId']
                    : null;
            } else {
                $response = false;

                if (isset($this->dataCrm['contact']['externalId'])) {
                    $response = RCrmActions::apiMethod(
                        $this->api,
                        'customersGet',
                        __METHOD__,
                        $this->dataCrm['contact']['externalId'],
                        $this->dataCrm['site']
                    );
                } elseif (isset($this->dataCrm['contact']['id'])) {
                    $response = RCrmActions::apiMethod(
                        $this->api,
                        'customersGetById',
                        __METHOD__,
                        $this->dataCrm['contact']['id'],
                        $this->dataCrm['site']
                    );
                }

                if ($response && isset($response['customer'])) {
                    $this->corporateContact = $response['customer'];
                    $this->orderCustomerExtId = isset($this->corporateContact['externalId'])
                        ? $this->corporateContact['externalId']
                        : null;
                }
            }
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
            $registerNewUser = true;

            if (!isset($this->dataCrm['customer']['email']) || empty($this->dataCrm['customer']['email'])) {
                if (RetailCrmOrder::isOrderCorporate($this->dataCrm) && !empty($this->corporateContact['email'])) {
                    $login = $this->corporateContact['email'];
                    $this->dataCrm['customer']['email'] = $this->corporateContact['email'];
                } else {
                    $login = uniqid('user_' . time()) . '@crm.com';
                    $this->dataCrm['customer']['email'] = $login;
                }
            }

            switch ($this->dbUser->SelectedRowsCount()) {
                case 0:
                    $login = $this->dataCrm['customer']['email'];
                    break;
                case 1:
                    $arUser = $this->dbUser->Fetch();
                    $registeredUserID = $arUser['ID'];
                    $registerNewUser = false;
                    break;
                default:
                    $login = uniqid('user_' . time()) . '@crm.com';
                    break;
            }

            if ($registerNewUser === true) {
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
                ->setPersonTypeId($this->dataCrm['contact']['externalId'])
                ->setUserId($this->contragentTypes['legal-entity']);
        }
    }

    public function buildAddress()
    {
        if (isset($this->dataCrm['company']['address'])) {
            $this->addressBuilder->setDataCrm($this->dataCrm['company']['address']);
            $this->addressBuilder->build();
            $this->customerAddress = $this->addressBuilder->customerAddress;
        } else {
            $this->customerAddress = null;
        }
    }
}
