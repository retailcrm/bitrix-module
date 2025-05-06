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
 * Class Customer
 *
 * @category RetailCRM
 * @package RetailCRM\Model
 */
class Customer extends BaseModel
{
    /**@var string $EMAIL */
    protected $EMAIL;

    /**@var string $LOGIN */
    protected $LOGIN;

    /**@var string $ACTIVE */
    protected $ACTIVE;

    /**@var string $PASSWORD */
    protected $PASSWORD;

    /**@var string $CONFIRM_PASSWORD */
    protected $CONFIRM_PASSWORD;

    /**@var string $NAME */
    protected $NAME;

    /**@var string $LAST_NAME */
    protected $LAST_NAME;

    /**@var string $SECOND_NAME */
    protected $SECOND_NAME;

    /**@var string $PERSONAL_MOBILE */
    protected $PERSONAL_MOBILE;

    /**@var string $PERSONAL_PHONE */
    protected $PERSONAL_PHONE;

    /**@var string $PHONE_NUMBER */
    protected $PHONE_NUMBER;

    /**@var string $PERSONAL_ZIP */
    protected $PERSONAL_ZIP;

    /**@var string $PERSONAL_CITY */
    protected $PERSONAL_CITY;

    /**@var string $PERSONAL_BIRTHDAY */
    protected $PERSONAL_BIRTHDAY;

    /**@var string $PERSONAL_GENDER */
    protected $PERSONAL_GENDER;

    /**@var string $UF_SUBSCRIBE_USER_EMAIL */
    protected $UF_SUBSCRIBE_USER_EMAIL;

    /**
     * @param string $EMAIL
     * @return $this
     */
    public function setEmail($EMAIL)
    {
        $this->EMAIL = $EMAIL;

        return $this;
    }

    /**
     * @param string $LOGIN
     * @return $this
     */
    public function setLogin($LOGIN)
    {
        $this->LOGIN = $LOGIN;

        return $this;
    }

    /**
     * @param string $ACTIVE
     * @return $this
     */
    public function setActive($ACTIVE)
    {
        $this->ACTIVE = $ACTIVE;

        return $this;
    }

    /**
     * @param string $PASSWORD
     * @return $this
     */
    public function setPassword($PASSWORD)
    {
        $this->PASSWORD = $PASSWORD;

        return $this;
    }

    /**
     * @param string $CONFIRM_PASSWORD
     * @return $this
     */
    public function setConfirmPassword($CONFIRM_PASSWORD)
    {
        $this->CONFIRM_PASSWORD = $CONFIRM_PASSWORD;

        return $this;
    }

    /**
     * @param string $NAME
     * @return $this
     */
    public function setName($NAME)
    {
        $this->NAME = $NAME;

        return $this;
    }

    /**
     * @param string $LAST_NAME
     * @return $this
     */
    public function setLastName($LAST_NAME)
    {
        $this->LAST_NAME = $LAST_NAME;

        return $this;
    }

    /**
     * @param string $SECOND_NAME
     * @return $this
     */
    public function setSecondName($SECOND_NAME)
    {
        $this->SECOND_NAME = $SECOND_NAME;

        return $this;
    }

    /**
     * @param string $PERSONAL_MOBILE
     * @return $this
     */
    public function setPersonalMobile($PERSONAL_MOBILE)
    {
        $this->PERSONAL_MOBILE = $PERSONAL_MOBILE;

        return $this;
    }

    /**
     * @param string $PERSONAL_PHONE
     * @return $this
     */
    public function setPersonalPhone($PERSONAL_PHONE)
    {
        $this->PERSONAL_PHONE = $PERSONAL_PHONE;

        return $this;
    }

    /**
     * @param string $PERSONAL_PHONE
     * @return $this
     */
    public function setPhone($PHONE_NUMBER)
    {
        $this->PHONE_NUMBER = $PHONE_NUMBER;

        return $this;
    }

    /**
     * @param string $PERSONAL_ZIP
     * @return $this
     */
    public function setPersonalZip($PERSONAL_ZIP)
    {
        $this->PERSONAL_ZIP = $PERSONAL_ZIP;

        return $this;
    }

    /**
     * @param string $PERSONAL_CITY
     * @return $this
     */
    public function setPersonalCity($PERSONAL_CITY)
    {
        $this->PERSONAL_CITY = $PERSONAL_CITY;

        return $this;
    }

    /**
     * @param string $PERSONAL_BIRTHDAY
     * @return $this
     */
    public function setPersonalBirthday($PERSONAL_BIRTHDAY)
    {
        $this->PERSONAL_BIRTHDAY = $PERSONAL_BIRTHDAY;

        return $this;
    }

    /**
     * @param string $PERSONAL_GENDER
     * @return $this
     */
    public function setPersonalGender($PERSONAL_GENDER)
    {
        $this->PERSONAL_GENDER = $PERSONAL_GENDER;

        return $this;
    }

    /**
     * @param string $UF_SUBSCRIBE_USER_EMAIL
     * @return $this
     */
    public function setSubscribe($UF_SUBSCRIBE_USER_EMAIL)
    {
        $this->UF_SUBSCRIBE_USER_EMAIL = $UF_SUBSCRIBE_USER_EMAIL;

        return $this;
    }
}
