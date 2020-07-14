<?php
/**
 * Class BuyerProfile
 */
class BuyerProfile extends BaseModel
{
    /**@var string $NAME */
    protected $NAME;

    /**@var string $USER_ID */
    protected $USER_ID;

    /**@var string $PERSON_TYPE_ID */
    protected $PERSON_TYPE_ID;

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
     * @param int $USER_ID
     * @return $this
     */
    public function setUserId($USER_ID)
    {
        $this->USER_ID = $USER_ID;

        return $this;
    }

    /**
     * @param int $PERSON_TYPE_ID
     * @return $this
     */
    public function setPersonTypeId($PERSON_TYPE_ID)
    {
        $this->PERSON_TYPE_ID = $PERSON_TYPE_ID;

        return $this;
    }
}
