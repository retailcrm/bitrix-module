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
        $this->customerAddress->setText($this->getValue('text'))
            ->setNotes($this->getValue('notes'))
            ->setBuilding($this->getValue('building'))
            ->setBlock($this->getValue('block'))
            ->setCity($this->getValue('city'))
            ->setFlat($this->getValue('flat'))
            ->setHouse($this->getValue('house'))
            ->setFloor($this->getValue('floor'))
            ->setCountry($this->getValue('countryIso'))
            ->setIndex($this->getValue('index'))
            ->setIntercomCode($this->getValue('intercomCode'))
            ->setMetro($this->getValue('metro'))
            ->setRegion($this->getValue('region'))
            ->setStreet($this->getValue('street'));

        return $this;
    }
    
    public function reset(): void
    {
        $this->customerAddress = new CustomerAddress();
    }
}
