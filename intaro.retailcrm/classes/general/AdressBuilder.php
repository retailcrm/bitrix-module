<?php

IncludeModuleLangFile(__FILE__);

/**
 * Class AdressBuilder
 */
class AdressBuilder implements RetailcrmBuilderInterface
{
    /** @var classes/general/Model/CustomerAddress */
    public $customerAddress;

    /** @var array $dataCrm customerHistory */
    protected $dataCrm;

    /**
     * CustomerBuilder constructor.
     */
    public function __construct()
    {
        $this->customerAddress = new CustomerAddress();
    }

    public function setDataCrm($dataCrm)
    {
        $this->dataCrm = $dataCrm;
        return $this;
    }

    /**
     * @param $array
     * @param $key
     * @param null $default
     * @return mixed|null
     */
    protected function getValue($array, $key, $default = NULL)
    {
        return isset($array[$key]) && !empty($array[$key]) ?  $array[$key] : $default;
    }

    public function build()
    {
        $this->customerAddress->setText($this->getValue($this->dataCrm,'text'))
            ->setNotes($this->getValue($this->dataCrm,'notes'))
            ->setBuilding($this->getValue($this->dataCrm,'building'))
            ->setBlock($this->getValue($this->dataCrm,'block'))
            ->setCity($this->getValue($this->dataCrm,'city'))
            ->setFlat($this->getValue($this->dataCrm,'flat'))
            ->setHouse($this->getValue($this->dataCrm,'house'))
            ->setFloor($this->getValue($this->dataCrm,'floor'))
            ->setCountry($this->getValue($this->dataCrm,'countryIso'))
            ->setIndex($this->getValue($this->dataCrm,'index'))
            ->setIntercomCode($this->getValue($this->dataCrm,'intercomCode'))
            ->setMetro($this->getValue($this->dataCrm,'metro'))
            ->setRegion($this->getValue($this->dataCrm,'region'))
            ->setStreet($this->getValue($this->dataCrm,'street'));

        return $this;
    }
}
