<?php

IncludeModuleLangFile(__FILE__);

/**
 * Class CustomerContragent
 */
class CustomerContragent
{
    public $contragentType;
    public $legalName;
    public $legalAddress;
    public $certificateNumber;
    public $certificateDate;
    public $bank;
    public $bankAddress;
    public $corrAccount;
    public $bankAccount;

    /**
     * @param $contragentType
     * @return $this
     */
    public function setContragentType($contragentType)
    {
        $this->contragentType = $contragentType;

        return $this;
    }

    /**
     * @param $legalName
     * @return $this
     */
    public function setLegalName($legalName)
    {
        $this->legalName = $legalName;

        return $this;
    }

    /**
     * @param $legalAddress
     * @return $this
     */
    public function setLegalAddress($legalAddress)
    {
        $this->legalAddress = $legalAddress;

        return $this;
    }

    /**
     * @param $certificateNumber
     * @return $this
     */
    public function setCertificateNumber($certificateNumber)
    {
        $this->certificateNumber = $certificateNumber;

        return $this;
    }

    /**
     * @param $certificateDate
     * @return $this
     */
    public function setCertificateDate($certificateDate)
    {
        $this->certificateDate = $certificateDate;

        return $this;
    }

    /**
     * @param $bank
     * @return $this
     */
    public function setBank($bank)
    {
        $this->bank = $bank;

        return $this;
    }

    /**
     * @param $bankAddress
     * @return $this
     */
    public function setBankAddress($bankAddress)
    {
        $this->bankAddress = $bankAddress;

        return $this;
    }

    /**
     * @param $corrAccount
     * @return $this
     */
    public function setCorrAccount($corrAccount)
    {
        $this->corrAccount = $corrAccount;

        return $this;
    }

    /**
     * @param $bankAccount
     * @return $this
     */
    public function setBankAccount($bankAccount)
    {
        $this->bankAccount = $bankAccount;

        return $this;
    }
}
