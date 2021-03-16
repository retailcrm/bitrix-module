<?php
/**
 * PHP version 7.1
 *
 * @category Integration
 * @package  Intaro\RetailCrm\Controller\Loyalty
 * @author   RetailCRM <integration@retailcrm.ru>
 * @license  MIT
 * @link     http://retailcrm.ru
 * @see      http://retailcrm.ru/docs
 */

namespace Intaro\RetailCrm\Controller\Loyalty;

use Bitrix\Main\ArgumentException;
use Bitrix\Main\ArgumentNullException;
use Bitrix\Main\Diag\Debug;
use Bitrix\Main\Engine\ActionFilter\Authentication;
use Bitrix\Main\Engine\ActionFilter\HttpMethod;
use Bitrix\Main\Engine\Controller;
use Bitrix\Main\Loader;
use Bitrix\Main\ObjectPropertyException;
use Bitrix\Main\SystemException;
use Exception;
use Intaro\RetailCrm\Component\Constants;
use Intaro\RetailCrm\Component\ServiceLocator;
use Intaro\RetailCrm\Model\Api\Response\Loyalty\LoyaltyCalculateResponse;
use Intaro\RetailCrm\Repository\PaySystemActionRepository;
use Intaro\RetailCrm\Service\LoyaltyService;
use Bitrix\Sale\Order as BitrixOrder;
use Intaro\RetailCrm\Service\LpUserAccountService;
use Intaro\RetailCrm\Service\Utils;

/**
 * Class Order
 *
 * @package Intaro\RetailCrm\Controller\Loyalty
 */
class Order extends Controller
{
    
    /**
     * Контроллер для пересчета бонусов
     *
     * @param array     $basketItems
     * @param float|int $inputBonuses
     *
     * @return \Intaro\RetailCrm\Model\Api\Response\Loyalty\LoyaltyCalculateResponse|null
     */
    public function calculateBonusAction(array $basketItems, float $inputBonuses = 0): ?LoyaltyCalculateResponse
    {
        /** @var LoyaltyService $service */
        $service  = ServiceLocator::get(LoyaltyService::class);
        $response = $service->calculateBonus($basketItems, $inputBonuses);

        if ($response instanceof LoyaltyCalculateResponse) {
            if ($response->success && count($response->order->items) > 0) {
                return $response;
            }
            
            Utils::handleErrors($response);
        }
        
        return null;
    }
    
    /**
     * @param string $verificationCode
     * @param int    $orderId
     * @param string $checkId
     * @return array
     */
    public function sendVerificationCodeAction(string $verificationCode, int $orderId, string $checkId): array
    {
        /** @var LpUserAccountService $service */
        $service  = ServiceLocator::get(LpUserAccountService::class);
        $response = $service->confirmVerification($verificationCode, $checkId);

        if ($response !== null && isset($response->errorMsg) && !empty($response->errorMsg)) {
            return [
                'status'   => 'error',
                'msg'      => 'Ошибка. ' . $response->errorMsg,
                'msgColor' => 'brown',
            ];
        }

        if ($response !== null
            && $response->success
            && isset($response->verification->verifiedAt)
            && !empty($response->verification->verifiedAt)
        ) {
            $loyaltyService = new LoyaltyService();
            
            $loyaltyService->setDebitedStatus($orderId, true);
            
            return [
                'status'   => 'success',
                'msg'      => GetMessage('BONUS_SUCCESS'),
                'msgColor' => 'green',
            ];
        }
        
        return [
            'status'   => 'error',
            'msg'      => GetMessage('BONUS_ERROR'),
            'msgColor' => 'brown',
        ];
    }
    
    /**
     * Повторно отправляет смс с кодом верификации
     *
     * @param $orderId
     * @return \Intaro\RetailCrm\Model\Bitrix\SmsCookie|array
     */
    public function resendOrderSmsAction($orderId)
    {
        /** @var LoyaltyService $service */
        $service = ServiceLocator::get(LoyaltyService::class);
    
        $result = $service->resendBonusPayment((int)$orderId);
        
        if ($result === true) {
            return ['msg' => GetMessage('BONUS_SUCCESS')];
        }
    
        if ($result === false) {
            return ['msg' => GetMessage('BONUS_ERROR')];
        }
 
        return $result;
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
            'resendOrderSms' => [
                '-prefilters' => [
                    new Authentication,
                    new HttpMethod(['POST']),
                ],
            ],
        ];
    }
}
