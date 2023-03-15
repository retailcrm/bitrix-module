<?php

/**
 * Class RetailCrmCollector
 */
class RetailCrmCollector
{
    public static $MODULE_ID = 'intaro.retailcrm';
    public static $CRM_COLL_KEY = 'coll_key';
    public static $CRM_COLL = 'collector';

    /**
     * Add Daemon Collector script
     *
     * @return bool
     */
    public static function add()
    {
        $keys = unserialize(COption::GetOptionString(self::$MODULE_ID, self::$CRM_COLL_KEY, 0));
        $collector = COption::GetOptionString(self::$MODULE_ID, self::$CRM_COLL, 0);
        $request = \Bitrix\Main\Context::getCurrent()->getRequest();

        if ($collector === 'Y' && !empty($keys[SITE_ID]) && $request->isAdminSection() !== true) {
            global $USER;

            $params = array();
            if ($USER->IsAuthorized()) {
                $params['customerId'] = $USER->GetID();
            }

            $str = "<script type=\"text/javascript\">
            (function(_,r,e,t,a,i,l){_['retailCRMObject']=a;_[a]=_[a]||function(){(_[a].q=_[a].q||[]).push(arguments)};_[a].l=1*new Date();l=r.getElementsByTagName(e)[0];i=r.createElement(e);i.async=!0;i.src=t;l.parentNode.insertBefore(i,l)})(window,document,'script','https://collector.retailcrm.pro/w.js','_rc');
            _rc('create', '" . $keys[SITE_ID] . "', " . json_encode((object) $params) . ");
            _rc('send', 'pageView');
            </script>";
            \Bitrix\Main\Page\Asset::getInstance()->addString($str, true);

            return true;
        } else {
            return false;
        }
    }
}
