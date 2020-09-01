<?php

namespace Intaro\RetailCrm\Model\Api;

use Intaro\RetailCrm\Component\Json\Mapping;

/**
 * Class SmsVerificationConfirm
 * @package Intaro\RetailCrm\Model\Api
 */
class SmsVerificationConfirm extends AbstractApiModel
{
    /**
     * Проверочный код
     *
     * @var string $code
     *
     * @Mapping\Type("string")
     * @Mapping\SerializedName("code")
     */
    protected $code;
    
    /**
     * Идентификатор проверки кода
     *
     * @var string $checkId
     *
     * @Mapping\Type("string")
     * @Mapping\SerializedName("checkId")
     */
    protected $checkId;
    
    /**
     * @param string $code
     */
    public function setCode(string $code): void
    {
        $this->code = $code;
    }
    
    /**
     * @param string $checkId
     */
    public function setCheckId(string $checkId): void
    {
        $this->checkId = $checkId;
    }
}