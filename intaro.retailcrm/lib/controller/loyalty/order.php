<?php

/**
 * @category Integration
 * @package  Intaro\RetailCrm\Controller\Loyalty
 * @author   RetailCRM <integration@retailcrm.ru>
 * @license  MIT
 * @link     http://retailcrm.ru
 * @see      http://retailcrm.ru/docs
 */

namespace Intaro\RetailCrm\Controller\Loyalty;

use Bitrix\Main\Engine\ActionFilter\Authentication;
use Bitrix\Main\Engine\ActionFilter\Csrf;
use Bitrix\Main\Engine\ActionFilter\HttpMethod;
use Bitrix\Main\Engine\Controller;
use Bitrix\Sale\Order as BitrixOrder;
use Intaro\RetailCrm\Component\ServiceLocator;
use Intaro\RetailCrm\Model\Api\Response\Loyalty\LoyaltyCalculateResponse;
use Intaro\RetailCrm\Repository\OrderLoyaltyDataRepository;
use Intaro\RetailCrm\Service\CookieService;
use Intaro\RetailCrm\Service\LoyaltyService;
use Intaro\RetailCrm\Service\LoyaltyAccountService;
use Intaro\RetailCrm\Service\OrderLoyaltyDataService;
use Intaro\RetailCrm\Service\Utils;
use RetailCrmOrder;

/**
 * Class Order
 *
 * @package Intaro\RetailCrm\Controller\Loyalty
 */
class Order extends Controller
{

    /**
     * Возвращает результат расчета привилегий программы лояльности
     *
     * @param array     $basketItems
     * @param float|int $inputBonuses
     *
     * @return \Intaro\RetailCrm\Model\Api\Response\Loyalty\LoyaltyCalculateResponse|null
     */
    public function loyaltyCalculateAction(array $basketItems, float $inputBonuses = 0): ?LoyaltyCalculateResponse
    {
        /** @var LoyaltyService $service */
        $service  = ServiceLocator::get(LoyaltyService::class);
        $response = $service->getLoyaltyCalculate($basketItems, $inputBonuses);

        if ($response instanceof LoyaltyCalculateResponse) {
            if ($response->success && count($response->order->items) > 0) {
                return $response;
            }

            Utils::handleApiErrors($response);
        }

        return null;
    }

    /**
     * Отправляет код верификации из смс в систему
     *
     * @param string $verificationCode Проверочный код
     * @param int    $orderId id заказа
     * @param string $checkId Идентификатор проверки кода
     * @return array
     */
    public function sendVerificationCodeAction(string $verificationCode, int $orderId, string $checkId): array
    {
        if (!$this->isOrderCheckIdValid($orderId, $checkId)) {
            return [
                'status'   => 'error',
                'msg'      => 'Forbidden',
                'msgColor' => 'brown',
            ];
        }

        /** @var LoyaltyAccountService $service */
        $service  = ServiceLocator::get(LoyaltyAccountService::class);
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
            /** @var LoyaltyService $loyaltyService */
            $loyaltyService = ServiceLocator::get(LoyaltyService::class);
            /** @var OrderLoyaltyDataService $orderLoyaltyDataService */
            $orderLoyaltyDataService = ServiceLocator::get(OrderLoyaltyDataService::class);
            $loyaltyService->setDebitedStatus($orderId, true);
            $orderLoyaltyDataService->updateOrderFromCrm($orderId);

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
     * Повторно отправляет смс с кодом верификации клиенту
     *
     * @param int $orderId id заказа
     * @return \Intaro\RetailCrm\Model\Bitrix\SmsCookie|array
     */
    public function resendOrderSmsAction(int $orderId)
    {
        if (!$this->canAccessOrder($orderId)) {
            return ['msg' => 'Forbidden'];
        }

        /** @var LoyaltyService $service */
        $service = ServiceLocator::get(LoyaltyService::class);
        $result = $service->resendBonusPayment($orderId);

        if ($result === true) {
            return ['msg' => GetMessage('BONUS_SUCCESS')];
        }

        if ($result === false) {
            return ['msg' => GetMessage('BONUS_ERROR')];
        }

        return $result;
    }

    private function canAccessOrder(int $orderId): bool
    {
        global $USER;

        $order = BitrixOrder::load($orderId);

        if ($order === null) {
            return false;
        }

        if ($USER instanceof \CUser
            && $USER->IsAuthorized()
            && (int) $order->getUserId() === (int) $USER->GetID()
        ) {
            return true;
        }

        /** @var CookieService $cookieService */
        $cookieService = ServiceLocator::get(CookieService::class);
        $smsCookie = $cookieService->getSmsCookie('lpOrderBonusConfirm');

        if ($smsCookie === null || empty($smsCookie->checkId)) {
            return false;
        }

        return $this->isOrderCheckIdValid($orderId, $smsCookie->checkId);
    }

    private function isOrderCheckIdValid(int $orderId, string $checkId): bool
    {
        if ($checkId === '') {
            return false;
        }

        $repository = new OrderLoyaltyDataRepository();
        $products = $repository->getProductsByOrderId($orderId);

        foreach ($products as $product) {
            if ((string) $product->checkId === $checkId) {
                return true;
            }
        }

        return false;
    }


    /**
     * @return \array[][]
     */
    public function configureActions(): array
    {
        return [
            'loyaltyCalculate' => [
                'prefilters' => [
                    new Authentication(),
                    new HttpMethod([HttpMethod::METHOD_POST]),
                    new Csrf(),
                ],
            ],
            'sendVerificationCode' => [
                'prefilters' => [
                    new Authentication(),
                    new HttpMethod([HttpMethod::METHOD_POST]),
                    new Csrf(),
                ],
            ],
            'resendOrderSms' => [
                'prefilters' => [
                    new Authentication(),
                    new HttpMethod([HttpMethod::METHOD_POST]),
                    new Csrf(),
                ],
            ],
        ];
    }
}
