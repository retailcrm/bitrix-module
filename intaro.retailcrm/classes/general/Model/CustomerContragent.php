<?php

/**
 * @category RetailCRM
 * @package  RetailCRM\Model
 * @author   RetailCRM <integration@retailcrm.ru>
 * @license  MIT
 * @link     http://retailcrm.ru
 * @see      http://retailcrm.ru/docs
 */

/**
 * Class CustomerContragent
 *
 * @category RetailCRM
 * @package RetailCRM\Model
 */
class CustomerContragent extends BaseModel
{
    /**@var string $contragentType */
    protected $contragentType;

    /**@var string $legalName */
    protected $legalName;

    /**@var string $legalAddress */
    protected $legalAddress;

    /**@var string $certificateNumber */
    protected $certificateNumber;

    /**@var string $certificateDate */
    protected $certificateDate;

    /**@var string $bank */
    protected $bank;

    /**@var string $bankAddress */
    protected $bankAddress;

    /**@var string $corrAccount */
    protected $corrAccount;

    /**@var string $bankAccount */
    protected $bankAccount;

    /**
     * @param string $contragentType
     * @return $this
     */
    public function setContragentType($contragentType)
    {
        $this->contragentType = $contragentType;

        return $this;
    }

    /**
     * @param string $legalName
     * @return $this
     */
    public function setLegalName($legalName)
    {
        $this->legalName = $legalName;

        return $this;
    }

    /**
     * @param string $legalAddress
     * @return $this
     */
    public function setLegalAddress($legalAddress)
    {
        $this->legalAddress = $legalAddress;

        return $this;
    }

    /**
     * @param string $certificateNumber
     * @return $this
     */
    public function setCertificateNumber($certificateNumber)
    {
        $this->certificateNumber = $certificateNumber;

        return $this;
    }

    /**
     * @param string $certificateDate
     * @return $this
     */
    public function setCertificateDate($certificateDate)
    {
        $this->certificateDate = $certificateDate;

        return $this;
    }

    /**
     * @param string $bank
     * @return $this
     */
    public function setBank($bank)
    {
        $this->bank = $bank;

        return $this;
    }

    /**
     * @param string $bankAddress
     * @return $this
     */
    public function setBankAddress($bankAddress)
    {
        $this->bankAddress = $bankAddress;

        return $this;
    }

    /**
     * @param string $corrAccount
     * @return $this
     */
    public function setCorrAccount($corrAccount)
    {
        $this->corrAccount = $corrAccount;

        return $this;
    }

    /**
     * @param string $bankAccount
     * @return $this
     */
    public function setBankAccount($bankAccount)
    {
        $this->bankAccount = $bankAccount;

        return $this;
    }
}
