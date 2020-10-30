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
use Bitrix\Main\Request;
use Intaro\RetailCrm\Component\ServiceLocator;
use Intaro\RetailCrm\Service\UserAccountService;

/**
 * Class SmsVerification
 * @package Intaro\RetailCrm\Controller\Loyalty
 */
class SmsVerification extends Controller
{
    /** @var int  */
    const DEFAULT_CODE_LENGHT = 4;
    
    /** @var UserAccountService */
    private $service;
    
    /**
     * AdminPanel constructor.
     *
     * @param \Bitrix\Main\Request|null $request
     */
    public function __construct(Request $request = null)
    {
        $this->service = ServiceLocator::get(UserAccountService::class);
        parent::__construct($request);
    }
    
    /**
     * Контроллер получает статус текущего состояния верификации
     *
     * @param string $checkId
     * @return \Intaro\RetailCrm\Model\Api\Response\SmsVerification\SmsVerificationStatusResponse|null
     */
    public function getSmsStatusAction(string $checkId)
    {
        return $this->service->getSmsStatus($checkId);
    }
    
    /**
     * Контроллер подтверждает верификацию
     *
     * @param string $code
     * @param string $checkId
     * @return \Intaro\RetailCrm\Model\Api\Response\SmsVerification\SmsVerificationConfirmResponse|null
     */
    public function confirmVerificationAction(string $code, string $checkId)
    {
        return $this->service->confirmVerification($code, $checkId);
    }
    
    /**
     * Контроллер проверяет, зарегистрирован ли пользователь в программе лояльности
     *
     * @param int $userId
     * @return bool
     */
    public function checkPlRegistrationStatusAction(int $userId)
    {
        return $this->service->checkPlRegistrationStatus($userId);
    }
    
    /**
     * @return \array[][]
     */
    public function configureActions(): array
    {
        return [
            'sendSms' => [
                '-prefilters' => [
                    new Authentication,
                    new HttpMethod(['GET']),
                ],
            ],
            'getSmsStatus' => [
                '-prefilters' => [
                    new Authentication,
                    new HttpMethod(['GET']),
                ],
            ],
            'confirmVerification' => [
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
}
