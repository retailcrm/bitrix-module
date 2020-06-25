<?php
class RetailCrmService
{
    const INTEGRATION_DELIVERY_ERROR = 'This value is used in integration delivery and can`t be changed through API.';

    public static function unsetIntegrationDeliveryFields($params, $errors)
    {
        foreach ($errors as $error) {
            if (strpos($error, self::INTEGRATION_DELIVERY_ERROR)) {
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
