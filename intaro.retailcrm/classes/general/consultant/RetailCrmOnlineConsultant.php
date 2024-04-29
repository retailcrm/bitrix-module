<?php

/**
 * @category RetailCRM
 * @package  RetailCRM\Consultant
 * @author   RetailCRM <integration@retailcrm.ru>
 * @license  MIT
 * @link     http://retailcrm.ru
 * @see      http://retailcrm.ru/docs
 */

/**
 * Class RetailCrmOnlineConsultant
 *
 * @category RetailCRM
 * @package RetailCRM\Consultant
 */
class RetailCrmOnlineConsultant
{
    /**
     * Add a script of online consultant
     * 
     * @return bool
     */
    public static function add()
    {
        $request = \Bitrix\Main\Context::getCurrent()->getRequest();

        if (RetailcrmConfigProvider::isOnlineConsultantEnabled() && $request->isAdminSection() !== true) {
            \Bitrix\Main\Page\Asset::getInstance()->addString(
                RetailcrmConfigProvider::getOnlineConsultantScript(), 
                true
            );
            
            return true;
        } else {
            return false;
        }
    }
}
