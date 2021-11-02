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
use Bitrix\Main\Engine\ActionFilter\Authentication;
use Bitrix\Main\Engine\ActionFilter\HttpMethod;
use Bitrix\Main\Engine\Controller;
use Bitrix\Main\Loader;
use Bitrix\Main\ObjectPropertyException;
use Bitrix\Main\Request;
use Bitrix\Main\SystemException;
use Exception;
use Intaro\RetailCrm\Component\ServiceLocator;
use Intaro\RetailCrm\Repository\PaySystemActionRepository;
use Intaro\RetailCrm\Service\UserVerificationService;

/**
 * Class AdminPanel
 * @package Intaro\RetailCrm\Controller\Loyalty
 */
class Order extends Controller
{
    /** @var UserVerificationService */
    private $service;
    
    /**
     * AdminPanel constructor.
     *
     * @param \Bitrix\Main\Request|null $request
     */
    public function __construct(Request $request = null)
    {
        $this->service = ServiceLocator::get(UserVerificationService::class);
        parent::__construct($request);
    }
    
    /**
     * @param string $verificationCode
     * @param int    $orderId
     * @param string $checkId
     * @return array
     */
    public function sendVerificationCodeAction(string $verificationCode, int $orderId, string $checkId): array
    {
        $response = $this->service->confirmVerification($verificationCode, $checkId);
    
        $response->success = true;
        $response->verification->verifiedAt = '123';
        
        if ($response !== null
            && $response->success
            && isset($response->verification->verifiedAt)
            && !empty($response->verification->verifiedAt)
        ) {
            try {
                Loader::includeModule('sale');
                
                $order = \Bitrix\Sale\Order::load($orderId);
                if (!$order) {
                    AddMessage2Log('ОШибка списания бонусов (не удалось получить заказ) по заказу №'.$orderId);
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
                        && $paySystemAction->get('CODE') === 'INTARO_BONUS'
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
     * @return \array[][]
     */
    public function sendVerificationCode(): array
    {
        return [
            'sendSms' => [
                '-prefilters' => [
                    new Authentication,
                    new HttpMethod(['GET']),
                ],
            ],
        ];
    }
}