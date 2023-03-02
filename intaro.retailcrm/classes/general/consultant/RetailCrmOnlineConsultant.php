<?php

/**
 * class RetailCrmOnlineConsultant
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
