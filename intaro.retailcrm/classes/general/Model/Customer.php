<?php
/**
 * Class Customer
 */
class Customer
{
    public $EMAIL;
    public $LOGIN;
    public $ACTIVE;
    public $PASSWORD;
    public $CONFIRM_PASSWORD;
    public $NAME;
    public $LAST_NAME;
    public $SECOND_NAME;
    public $PERSONAL_MOBILE;
    public $PERSONAL_PHONE;
    public $PERSONAL_ZIP;
    public $PERSONAL_CITY;
    public $PERSONAL_BIRTHDAY;
    public $PERSONAL_GENDER;

    /**
     * @param $EMAIL
     * @return $this
     */
    public function setEmail($EMAIL)
    {
        $this->EMAIL = $EMAIL;

        return $this;
    }

    /**
     * @param $LOGIN
     * @return $this
     */
    public function setLogin($LOGIN)
    {
        $this->LOGIN = $LOGIN;

        return $this;
    }

    /**
     * @param $ACTIVE
     * @return $this
     */
    public function setActive($ACTIVE)
    {
        $this->ACTIVE = $ACTIVE;

        return $this;
    }

    /**
     * @param $PASSWORD
     * @return $this
     */
    public function setPassword($PASSWORD)
    {
        $this->PASSWORD = $PASSWORD;

        return $this;
    }

    /**
     * @param $CONFIRM_PASSWORD
     * @return $this
     */
    public function setConfirmPassword($CONFIRM_PASSWORD)
    {
        $this->CONFIRM_PASSWORD = $CONFIRM_PASSWORD;

        return $this;
    }

    /**
     * @param $NAME
     * @return $this
     */
    public function setName($NAME)
    {
        $this->NAME = $NAME;

        return $this;
    }

    /**
     * @param $LAST_NAME
     * @return $this
     */
    public function setLastName($LAST_NAME)
    {
        $this->LAST_NAME = $LAST_NAME;

        return $this;
    }

    /**
     * @param $SECOND_NAME
     * @return $this
     */
    public function setSecondName($SECOND_NAME)
    {
        $this->SECOND_NAME = $SECOND_NAME;

        return $this;
    }

    /**
     * @param $PERSONAL_MOBILE
     * @return $this
     */
    public function setPersonalMobile($PERSONAL_MOBILE)
    {
        $this->PERSONAL_MOBILE = $PERSONAL_MOBILE;

        return $this;
    }

    /**
     * @param $PERSONAL_PHONE
     * @return $this
     */
    public function setPersonalPhone($PERSONAL_PHONE)
    {
        $this->PERSONAL_PHONE = $PERSONAL_PHONE;

        return $this;
    }

    /**
     * @param $PERSONAL_ZIP
     * @return $this
     */
    public function setPersonalZip($PERSONAL_ZIP)
    {
        $this->PERSONAL_ZIP = $PERSONAL_ZIP;

        return $this;
    }

    /**
     * @param $PERSONAL_CITY
     * @return $this
     */
    public function setPersonalCity($PERSONAL_CITY)
    {
        $this->PERSONAL_CITY = $PERSONAL_CITY;

        return $this;
    }

    /**
     * @param $PERSONAL_BIRTHDAY
     * @return $this
     */
    public function setPersonalBirthday($PERSONAL_BIRTHDAY)
    {
        $this->PERSONAL_BIRTHDAY = $PERSONAL_BIRTHDAY;

        return $this;
    }

    /**
     * @param $PERSONAL_GENDER
     * @return $this
     */
    public function setPersonalGender($PERSONAL_GENDER)
    {
        $this->PERSONAL_GENDER = $PERSONAL_GENDER;

        return $this;
    }
}
