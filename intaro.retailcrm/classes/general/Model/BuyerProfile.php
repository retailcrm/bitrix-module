<?php
/**
 * Class BuyerProfile
 */
class BuyerProfile
{
    public $NAME;
    public $USER_ID;
    public $PERSON_TYPE_ID;

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
     * @param $USER_ID
     * @return $this
     */
    public function setUserId($USER_ID)
    {
        $this->USER_ID = $USER_ID;

        return $this;
    }

    /**
     * @param $PERSON_TYPE_ID
     * @return $this
     */
    public function setPersonTypeId($PERSON_TYPE_ID)
    {
        $this->PERSON_TYPE_ID = $PERSON_TYPE_ID;

        return $this;
    }
}
