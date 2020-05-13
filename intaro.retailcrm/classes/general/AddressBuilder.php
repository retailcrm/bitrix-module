<?php
/**
 * Class AddressBuilder
 */
class AddressBuilder extends AbstractBuilder implements RetailcrmBuilderInterface
{
    /**
     * @var CustomerAddress
     */
    private $customerAddress;

    /** @var array $dataCrm customerHistory */
    protected $dataCrm;

    /**
     * CustomerBuilder constructor.
     */
    public function __construct()
    {
        $this->customerAddress = new CustomerAddress();
    }

    /**
     * @param array $dataCrm
     * @return $this|RetailcrmBuilderInterface
     */
    public function setDataCrm($dataCrm)
    {
        $this->dataCrm = $dataCrm;
        return $this;
    }

    /**
     * @param $data
     * @return $this
     */
    public function setCustomerAddress($data)
    {
        $this->customerAddress = $data;
        return $this;
    }

    /**
     * @return CustomerAddress
     */
    public function getCustomerAddress()
    {
        return $this->customerAddress;
    }

    public function build()
    {
        $this->customerAddress->setText($this->getValueArray($this->dataCrm,'text'))
            ->setNotes($this->getValueArray($this->dataCrm,'notes'))
            ->setBuilding($this->getValueArray($this->dataCrm,'building'))
            ->setBlock($this->getValueArray($this->dataCrm,'block'))
            ->setCity($this->getValueArray($this->dataCrm,'city'))
            ->setFlat($this->getValueArray($this->dataCrm,'flat'))
            ->setHouse($this->getValueArray($this->dataCrm,'house'))
            ->setFloor($this->getValueArray($this->dataCrm,'floor'))
            ->setCountry($this->getValueArray($this->dataCrm,'countryIso'))
            ->setIndex($this->getValueArray($this->dataCrm,'index'))
            ->setIntercomCode($this->getValueArray($this->dataCrm,'intercomCode'))
            ->setMetro($this->getValueArray($this->dataCrm,'metro'))
            ->setRegion($this->getValueArray($this->dataCrm,'region'))
            ->setStreet($this->getValueArray($this->dataCrm,'street'));

        return $this;
    }
}
