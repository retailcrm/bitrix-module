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
        if (RetailcrmConfigProvider::isOnlineConsultantEnabled() && ADMIN_SECTION !== true) {
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
