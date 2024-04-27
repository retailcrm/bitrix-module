<?php

/**
 * @category RetailCRM
 * @package  RetailCRM\Service
 * @author   RetailCRM <integration@retailcrm.ru>
 * @license  MIT
 * @link     http://retailcrm.ru
 * @see      http://retailcrm.ru/docs
 */

use Intaro\RetailCrm\Component\Constants;

/**
 * Class RetailCrmService
 *
 * @category RetailCRM
 * @package RetailCRM\Service
 */
class RetailCrmService
{
    /**
     * @param $order
     *
     * @return array
     */
    public static function unsetIntegrationDeliveryFields(array $order): array
    {
        $integrationDelivery = RetailcrmConfigProvider::getCrmIntegrationDelivery();

        if (isset($order['delivery']['code'])) {
            $deliveryCode = $order['delivery']['code'];

            if (!empty($integrationDelivery[$deliveryCode])
                && $integrationDelivery[$deliveryCode] !== 'sdek'
                && $integrationDelivery[$deliveryCode] !== 'dpd'
                && $integrationDelivery[$deliveryCode] !== 'newpost'
                && $integrationDelivery[$deliveryCode] !== 'courier'
            ) {
                unset($order['weight']);
                unset($order['firstName']);
                unset($order['lastName']);
                unset($order['phone']);
                unset($order['delivery']['cost']);
                unset($order['paymentType']);
                unset($order['shipmentStore']);
                unset($order['delivery']['address']);
                unset($order['delivery']['data']);
            }

            switch ($integrationDelivery[$deliveryCode]) {
                case "sdek":
                    unset($order['weight']);
                    unset($order['length']);
                    unset($order['width']);
                    unset($order['height']);
                    unset($order['phone']);
                    unset($order['delivery']['cost']);
                    unset($order['paymentType']);
                    unset($order['shipmentStore']);
                    unset($order['number']);
                    unset($order['delivery']['address']);
                    unset($order['delivery']['data']);
                    break;
                case "dpd":
                    unset($order['weight']);
                    unset($order['manager']);
                    unset($order['phone']);
                    unset($order['firstName']);
                    unset($order['lastName']);
                    unset($order['delivery']['cost']);
                    unset($order['paymentType']);
                    unset($order['shipmentStore']);
                    unset($order['delivery']['address']);
                    unset($order['delivery']['data']);
                    break;
                case "newpost":
                    unset($order['weight']);
                    unset($order['customer']);
                    unset($order['phone']);
                    unset($order['shipmentStore']);
                    unset($order['paymentType']);
                    unset($order['delivery']['cost']);
                    unset($order['delivery']['address']);
                    unset($order['delivery']['data']);
                    break;
            }
        }

        return $order;
    }

    /**
     * @param array $data
     *
     * @return array
     */
    public static function selectIntegrationDeliveries(array $data): array
    {
        $result = [];

        foreach ($data as $elem) {
            if (!empty($elem['integrationCode'])) {
                $result[$elem['code']] = $elem['integrationCode'];
            }
        }

        return $result;
    }

    /**
     * @param array $data
     *
     * @return array
     */
    public static function selectIntegrationPayments(array $data): array
    {
        $result = [];

        foreach ($data as $elem) {
            if (!empty($elem['integrationModule'])) {
                $result[] = $elem['code'];
            }
        }

        return $result;
    }
    
    /**
     * @param int|null $paySystemId
     *
     * @return bool
     */
    public static function isIntegrationPayment(?int $paySystemId): bool {
        return in_array(
            RetailcrmConfigProvider::getPaymentTypes()[$paySystemId] ?? null,
            RetailcrmConfigProvider::getIntegrationPaymentTypes(),
            true
        );
    }

    /**
     * @param array|null $availableSites
     * @param array|null $types
     *
     * @return array
     */
    public static function getAvailableTypes(?array $availableSites, ?array $types)
    {
        $result = [];

        foreach ($types as $type) {
            if ($type['active'] !== true) {
                continue;
            }

            if (empty($type['sites'])) {
                $result[$type['code']] = $type;
            } else {
                foreach ($type['sites'] as $site) {
                    if (!empty($availableSites[$site])) {
                        $result[$type['code']] = $type;
                        break;
                    }
                }
            }
        }

        return $result;
    }

    /**
     * @param array $arFields
     * @return void
     */
    public static function writeLogsSubscribe(array $arFields): void
    {
        if (array_key_exists('UF_SUBSCRIBE_USER_EMAIL', $arFields)) {
            $actionSub = GetMessage('SUBSCRIBED_USER');
            $fileSub = 'subscribe';

            if (empty($arFields['UF_SUBSCRIBE_USER_EMAIL'])) {
                $actionSub = GetMessage('UNSUBSCRIBED_USER');
                $fileSub = 'unSubscribe';
            }

            $id = $arFields['ID'] ?? $arFields['USER_ID'];

            Logger::getInstance()->write(
                $actionSub . ' (' . $id . ')',
                $fileSub
            );
        }
    }

    /**
     * @param array $crmPayment
     * @param array $bitrixPayment
     * @param array $optionsPaymentTypes
     * @return array mixed
     */
    public static function preparePayment($crmPayment, $bitrixPayment, $optionsPaymentTypes)
    {
        $isIntegrationPayment = self::isIntegrationPayment($bitrixPayment['PAY_SYSTEM_ID'] ?? null);

        if ($isIntegrationPayment && RetailcrmConfigProvider::getSyncIntegrationPayment() === 'Y') {
            $crmPayment['type'] = $optionsPaymentTypes[$bitrixPayment['PAY_SYSTEM_ID']] .
                Constants::CRM_PART_SUBSTITUTED_PAYMENT_CODE;
        } else {
            $crmPayment['type'] = $optionsPaymentTypes[$bitrixPayment['PAY_SYSTEM_ID']];

            if ($isIntegrationPayment) {
                unset($crmPayment['paidAt'], $crmPayment['status']);
            }
        }

        return $crmPayment;
    }

}
