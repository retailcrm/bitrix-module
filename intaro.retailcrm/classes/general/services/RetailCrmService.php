<?php
class RetailCrmService
{
<<<<<<< HEAD
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
=======
    const INTAGRATION_DELIVERY_ERROR = 'This value is used in integration delivery and can`t be changed through API.';

    public static function unsetIntegrationDeliveryFields($params, $errors)
    {
        foreach ($errors as $error) {
            if (strpos($error, self::INTAGRATION_DELIVERY_ERROR)) {
                $matches = [];
                // Serch for array keys in error message
                preg_match_all('/(?<=\[).+?(?=\])/', $error, $matches);
                $keys = $matches[0];
                if (count($matches[0]) == 2) {
                    unset($params[0][$keys[0]][$keys[1]]);
                } else {
                    unset($params[0][$keys[0]]);
                }
            }
        }

        return $params;
    }
}
>>>>>>> 5b35ffa... new service for generate valid order with integration delivery and tests
