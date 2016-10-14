<?php
IncludeModuleLangFile(__FILE__);
class RCrmActions
{
    public static $MODULE_ID = 'intaro.retailcrm';
    public static $CRM_ORDER_FAILED_IDS = 'order_failed_ids';

    const CANCEL_PROPERTY_CODE = 'INTAROCRM_IS_CANCELED';

    public static function SitesList()
    {
        $arSites = array();
        $rsSites = CSite::GetList($by, $sort, array('ACTIVE' => 'Y'));
        while ($ar = $rsSites->Fetch()) {
            $arSites[] = $ar;   
        }
        
        return $arSites;
    }
    
    public static function OrderTypesList($arSites)
    {
        $orderTypesList = array();            
        foreach ($arSites as $site) {
            $personTypes = \Bitrix\Sale\PersonType::load($site['LID']);
            $bitrixOrderTypesList = array();
            foreach ($personTypes as $personType) {
                if (!array_key_exists($personType['ID'], $orderTypesList)) {
                    $bitrixOrderTypesList[$personType['ID']] = $personType;
                }
                asort($bitrixOrderTypesList);
            }
            $orderTypesList = $orderTypesList + $bitrixOrderTypesList;
        }
        
        return $orderTypesList;
    }
    
    public static function DeliveryList()
    {
        $bitrixDeliveryTypesList = array();
        $arDeliveryServiceAll = \Bitrix\Sale\Delivery\Services\Manager::getActiveList();
        $noOrderId = \Bitrix\Sale\Delivery\Services\EmptyDeliveryService::getEmptyDeliveryServiceId();
        foreach ($arDeliveryServiceAll as $arDeliveryService) {
            if ($arDeliveryService['PARENT_ID'] == '0' && $arDeliveryService['ID'] != $noOrderId) {
                $bitrixDeliveryTypesList[] = $arDeliveryService;
            }
        }
        
        return $bitrixDeliveryTypesList;
    }  
    
    public static function PaymentList()
    {
        $bitrixPaymentTypesList = array();
        $dbPaymentAll = \Bitrix\Sale\PaySystem\Manager::getList(array(
            'select' => array('ID', 'NAME'),
            'filter' => array('ACTIVE' => 'Y')
        ));
        while ($payment = $dbPaymentAll->fetch()) {
            $bitrixPaymentTypesList[] = $payment;
        }
        
        return $bitrixPaymentTypesList;
    }   
    
    public static function StatusesList()
    {
        $bitrixPaymentStatusesList = array();
        $obStatuses = \Bitrix\Sale\Internals\StatusTable::getList(array(
            'filter' => array('TYPE' => 'O'),
            'select' => array('ID', "NAME" => 'Bitrix\Sale\Internals\StatusLangTable:STATUS.NAME')
        ));
        while ($arStatus = $obStatuses->fetch()) {
            $bitrixPaymentStatusesList[$arStatus['ID']] = array(
                'ID'   => $arStatus['ID'],
                'NAME' => $arStatus['NAME'],
            );
        }
        
        return $bitrixPaymentStatusesList;
    }   
    
    public static function OrderPropsList()
    {
        $bitrixPropsList = array();
        $arPropsAll = \Bitrix\Sale\Internals\OrderPropsTable::getList(array(
            'select' => array('*')
        ));
        while ($prop = $arPropsAll->Fetch()) {
            $bitrixPropsList[$prop['PERSON_TYPE_ID']][] = $prop;
        }
        
        return $bitrixPropsList;
    } 
    /**
     *
     * w+ event in bitrix log
     */

    public static function eventLog($auditType, $itemId, $description)
    {
        CEventLog::Add(array(
            "SEVERITY"      => "SECURITY",
            "AUDIT_TYPE_ID" => $auditType,
            "MODULE_ID"     => self::$MODULE_ID,
            "ITEM_ID"       => $itemId,
            "DESCRIPTION"   => $description,
        ));
    }

    /**
     *
     * Agent function
     *
     * @return self name
     */

    public static function uploadOrdersAgent()
    {
        RetailCrmOrder::uploadOrders();
        $failedIds = unserialize(COption::GetOptionString(self::$MODULE_ID, self::$CRM_ORDER_FAILED_IDS, 0));
        if (is_array($failedIds) && !empty($failedIds)) {
            RetailCrmOrder::uploadOrders(50, true);
        }

        return 'RCrmActions::uploadOrdersAgent();';
    }

    /**
     *
     * Agent function
     *
     * @return self name
     */

    public static function orderAgent()
    {
        if (COption::GetOptionString('main', 'agents_use_crontab', 'N') != 'N') {
            define('NO_AGENT_CHECK', true);
        }

        RetailCrmHistory::customerHistory();
        RetailCrmHistory::orderHistory();
        self::uploadOrdersAgent();
        
        return 'RCrmActions::orderAgent();';
    }

    /**
     * removes all empty fields from arrays
     * working with nested arrs
     *
     * @param array $arr
     * @return array
     */
    public static function clearArr($arr)
    {
        if (is_array($arr) === false) {
            return $arr;
        }

        $result = array();
        foreach ($arr as $index => $node ) {
            $result[ $index ] = is_array($node) === true ? self::clearArr($node) : trim($node);
            if ($result[ $index ] == '' || $result[ $index ] === null || count($result[ $index ]) < 1) {
                unset($result[ $index ]);
            }
        }

        return $result;
    }

    /**
     *
     * @global $APPLICATION
     * @param $str in SITE_CHARSET
     * @return  $str in utf-8
     */
    public static function toJSON($str)
    {
        global $APPLICATION;

        return $APPLICATION->ConvertCharset($str, SITE_CHARSET, 'utf-8');
    }

    /**
     *
     * @global $APPLICATION
     * @param $str in utf-8
     * @return $str in SITE_CHARSET
     */
    public static function fromJSON($str)
    {
        global $APPLICATION;

        return $APPLICATION->ConvertCharset($str, 'utf-8', SITE_CHARSET);
    }

    public static function explodeFIO($fio)
    {
        $newFio = empty($fio) ? false : explode(" ", $fio, 3);
        $result = array();
        switch (count($newFio)) {
            default:
            case 0:
                $result['firstName']  = $fio;
                break;
            case 1:
                $result['firstName']  = $newFio[0];
                break;
            case 2:
                $result = array(
                    'lastName'  => $newFio[0],
                    'firstName' => $newFio[1]
                );
                break;
            case 3:
                $result = array(
                    'lastName'   => $newFio[0],
                    'firstName'  => $newFio[1],
                    'patronymic' => $newFio[2]
                );
                break;
        }

        return $result;
    }

    public static function apiMethod($api, $methodApi, $method, $params, $site = null)
    {
        switch ($methodApi) {
            case 'ordersGet':
            case 'ordersEdit':
            case 'customersGet':
            case 'customersEdit':
                try {
                    $result = $api->$methodApi($params, 'externalId', $site);
                } catch (\RetailCrm\Exception\CurlException $e) {
                    self::eventLog(
                        __CLASS__.'::'.$method, 'RetailCrm\ApiClient::'.$methodApi.'::CurlException',
                        $e->getCode() . ': ' . $e->getMessage()
                    );

                    return false;
                } catch (InvalidArgumentException $e) {
                    self::eventLog(
                        __CLASS__.'::'.$method, 'RetailCrm\ApiClient::'.$methodApi.'::InvalidArgumentException',
                        $e->getCode() . ': ' . $e->getMessage()
                    );
                    
                    return false;
                }
                return $result;

            default:
                try {
                    $result = $api->$methodApi($params, $site);
                } catch (\RetailCrm\Exception\CurlException $e) {
                    self::eventLog(
                        __CLASS__.'::'.$method, 'RetailCrm\ApiClient::'.$methodApi.'::CurlException',
                        $e->getCode() . ': ' . $e->getMessage()
                    );

                    return false;
                } catch (InvalidArgumentException $e) {
                    self::eventLog(
                        __CLASS__.'::'.$method, 'RetailCrm\ApiClient::'.$methodApi.'::InvalidArgumentException',
                        $e->getCode() . ': ' . $e->getMessage()
                    );
                    
                    return false;
                }
                return $result;
        }        
    }
}
