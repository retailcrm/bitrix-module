<?php

/**
 * Class SmsVerificationCreate
 */
class SmsVerificationCreate extends BaseModel
{
    /**@var integer $length */
    protected $length;
    
    /**@var string $phone */
    protected $phone;
    
    /**@var string $actionType */
    protected $actionType;
    
    /**@var integer $customerId */
    protected $customerId;
    
    /**@var integer $orderId */
    protected $orderId;
    
    /**
     * @param int $length
     */
    public function setLength(int $length): void
    {
        $this->length = $length;
    }
    
    /**
     * @param string $phone
     */
    public function setPhone(string $phone): void
    {
        $this->phone = $phone;
    }
    
    /**
     * @param string $actionType
     */
    public function setActionType(string $actionType): void
    {
        $this->actionType = $actionType;
    }
    
    /**
     * @param int $customerId
     */
    public function setCustomerId(int $customerId): void
    {
        $this->customerId = $customerId;
    }
    
    /**
     * @param int $orderId
     */
    public function setOrderId(int $orderId): void
    {
        $this->orderId = $orderId;
    }
}
