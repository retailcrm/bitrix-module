<?php
class RetailCrmService
{
    const MID = 'intaro.retailcrm';

    public static function unsetIntegrationDeliveryFields($order)
    {
        $integrationDelivery = unserialize(COption::GetOptionString(self::MID, "integrationDelivery", 0));
        $deliveryCode = $order['delivery']['code'];
        if ($deliveryCode) {
            switch ($integrationDelivery[$deliveryCode]) {
                case "sdek":
                    unset($order['number']);
                    unset($order['height']);
                    unset($order['length']);
                    unset($order['width']);
                    break;
                case "dpd":
                    unset($order['manager']);
                    unset($order['firstName']);
                    unset($order['lastName']);
                    break;
                case "newpost":
                    unset($order['customer']);
                    break;
                default:
                    unset($order['firstName']);
                    unset($order['lastName']);
            }

            unset($order['weight']);
            unset($order['phone']);
            unset($order['deliveryCost']);
            unset($order['paymentType']);
            unset($order['shipmentStore']);
            unset($order['delivery']['address']);
            unset($order['delivery']['data']);
        }

        return $order;
    }
}
