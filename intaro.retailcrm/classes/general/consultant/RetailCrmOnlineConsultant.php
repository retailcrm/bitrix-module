<?php

/**
 * class RetailCrmConsultant
 */
class RetailCrmConsultant
{
    /**
     * Add a script of online consultant
     */
    public static function add()
    {
        $CRM_ONLINE_CONSULTANT = RetailcrmConstants::CRM_ONLINE_CONSULTANT;
        $CRM_ONLINE_CONSULTANT_SCRIPT = RetailcrmConstants::CRM_ONLINE_CONSULTANT_SCRIPT;

        $onlineConsultant = RetailcrmConfigProvider::getOption($CRM_ONLINE_CONSULTANT);

        if ($consultant === 'Y' && ADMIN_SECTION !== true) {
            $onlineConsultantScript = RetailcrmConfigProvider::getOnlineConsultantScript($CRM_ONLINE_CONSULTANT_SCRIPT);
            \Bitrix\Main\Page\Asset::getInstance()->addString($onlineConsultantScript, true);
            
            return true;
        } else {
            return false;
        }
    }
}