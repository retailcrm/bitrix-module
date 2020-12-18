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
use Intaro\RetailCrm\Repository\PaySystemActionRepository;
use Intaro\RetailCrm\Service\LoyaltyService;
use Bitrix\Sale\Order as BitrixOrder;
use Intaro\RetailCrm\Service\LpUserAccountService;

/**
 * Class Order
 *
 * @package Intaro\RetailCrm\Controller\Loyalty
 */
class Order extends Controller
{
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
            try {
                Loader::includeModule('sale');
                
                $order = BitrixOrder::load($orderId);
                
                if (!$order) {
                    AddMessage2Log('Ошибка списания бонусов (не удалось получить объект Order) по заказу №' . $orderId);
                    return [
                        'status'   => 'error',
                        'msg'      => 'Ошибка',
                        'msgColor' => 'brown',
                    ];
                }
                
                $paymentCollection = $order->getPaymentCollection();
                
                /** @var \Bitrix\Sale\Payment $payment */
                foreach ($paymentCollection as $payment) {
                    $isPaid = $payment->isPaid();
    
                    try {
                        $paySystemAction = PaySystemActionRepository::getFirstByWhere(
                            ['*'],
                            [
                                ['ID', '=', $payment->getField('PAY_SYSTEM_ID')],
                            ]
                        );
                    } catch (ObjectPropertyException | ArgumentException | SystemException $e) {
                        AddMessage2Log($e->getMessage());
                    }
                    
                    if (isset($paySystemAction)
                        && !$isPaid
                        && $paySystemAction->get('CODE') === Constants::BONUS_PAYMENT_CODE
                    ) {
                        $payment->setPaid('Y');
                        $order->save();
                    }
                }
            } catch (Exception | ArgumentNullException $exception) {
                AddMessage2Log($exception->getMessage());
            }
            
            return [
                'status'   => 'success',
                'msg'      => 'Бонусы успешно списаны',
                'msgColor' => 'green',
            ];
        }
        
        return [
            'status'   => 'error',
            'msg'      => 'Ошибка',
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
        Debug::writeToFile(json_encode($result), '', 'log.txt');
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
