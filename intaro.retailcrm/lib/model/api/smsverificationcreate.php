<?php

namespace Intaro\RetailCrm\Model\Api;

/**
 * Class SmsVerificationCreate
 *
 * @package Intaro\RetailCrm\Model\Api
 */
class SmsVerificationCreate extends AbstractApiModel
{
    /**
     * Длина кода в сообщении (по умолчанию 4 символа)
     *
     * @var integer $length
     *
     * @Mapping\Type("integer")
     * @Mapping\SerializedName("length")
     */
    protected $length;
    
    /**
     * Номер телефона для отправки сообщения
     *
     * @var string $phone
     *
     * @Mapping\Type("string")
     * @Mapping\SerializedName("phone")
     */
    protected $phone;
    
    /**
     * Тип события, для которого необходима верификация
     *
     * @var string $actionType
     *
     * @Mapping\Type("string")
     * @Mapping\SerializedName("actionType")
     */
    protected $actionType;
    
    /**
     * ID клиента
     *
     * @var integer $customerId
     *
     * @Mapping\Type("integer")
     * @Mapping\SerializedName("customerId")
     */
    protected $customerId;
    
    /**
     * ID заказа
     *
     * @var integer $orderId
     *
     * @Mapping\Type("integer")
     * @Mapping\SerializedName("orderId")
     */
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
