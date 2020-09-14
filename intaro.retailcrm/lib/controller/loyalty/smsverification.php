<?php
/**
 * PHP version 7.1
 *
 * @category Integration
 * @package  Intaro\RetailCrm\Controller\Loyalty
 * @author   retailCRM <integration@retailcrm.ru>
 * @license  MIT
 * @link     http://retailcrm.ru
 * @see      http://retailcrm.ru/docs
 */
namespace Intaro\RetailCrm\Controller\Loyalty;

use Bitrix\Main\Engine\ActionFilter\Authentication;
use Bitrix\Main\Engine\ActionFilter\HttpMethod;
use Bitrix\Main\Engine\Controller;
use Intaro\RetailCrm\Service\UserVerificationService;

class AdminPanel extends Controller
{
    
    const DEFAULT_CODE_LENGHT = 4;
    
    public function configureActions(): array
    {
        return [
            'sendSms'                   => [
                '-prefilters' => [
                    new Authentication,
                    new HttpMethod(['GET']),
                ],
            ],
            'getSmsStatus'              => [
                '-prefilters' => [
                    new Authentication,
                    new HttpMethod(['GET']),
                ],
            ],
            'confirmVerification'       => [
                '-prefilters' => [
                    new Authentication,
                    new HttpMethod(['GET']),
                ],
            ],
            'checkPlRegistrationStatus' => [
                '-prefilters' => [
                    new Authentication,
                    new HttpMethod(['GET']),
                ],
            ],
        ];
    }
    
    /**
     * send verification sms
     *
     * @param string   $actionType
     * @param int|null $orderId
     * @param int      $verificationLength
     * @return \Intaro\RetailCrm\Model\Api\Response\SmsVerification\SmsVerificationCreateResponse|null
     * @throws \Exception
     */
    public function sendSmsAction(
        string $actionType = 'verify_customer',
        int $orderId = null,
        int $verificationLength = self::DEFAULT_CODE_LENGHT
    ) {
        $service = new UserVerificationService();
        return $service->sendSms($actionType, $orderId, $verificationLength);
    }
    
    /**
     * @param string $checkId
     * @return \Intaro\RetailCrm\Model\Api\Response\SmsVerification\SmsVerificationStatusResponse|null
     */
    public function getSmsStatusAction(string $checkId)
    {
        $service = new UserVerificationService();
        return $service->getSmsStatus($checkId);
    }
    
    /**
     * @param string $code
     * @param string $checkId
     * @return \Intaro\RetailCrm\Model\Api\Response\SmsVerification\SmsVerificationConfirmResponse|null
     */
    public function confirmVerificationAction(string $code, string $checkId)
    {
        $service = new UserVerificationService();
        return $service->confirmVerification($code, $checkId);
    }
    
    /**
     * @param int $userId
     * @return bool
     */
    public function checkPlRegistrationStatusAction(int $userId)
    {
        $service = new UserVerificationService();
        return $service->checkPlRegistrationStatus($userId);
    }
    
}
