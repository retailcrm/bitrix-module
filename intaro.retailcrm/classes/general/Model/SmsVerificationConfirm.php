<?php

/**
 * Class SmsVerificationConfirm
 */
class SmsVerificationConfirm extends BaseModel
{
    /**@var string $code */
    protected $code;
    
    /**@var string $checkId */
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
