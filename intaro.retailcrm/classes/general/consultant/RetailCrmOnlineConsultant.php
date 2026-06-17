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
        $scriptUrl = RetailcrmConfigProvider::getOnlineConsultantScriptUrl();

        if (
            RetailcrmConfigProvider::isOnlineConsultantEnabled()
            && $request->isAdminSection() !== true
            && $scriptUrl !== ''
        ) {
            \Bitrix\Main\Page\Asset::getInstance()->addString(
                sprintf(
                    '<script async src="%s"></script>',
                    htmlspecialcharsbx($scriptUrl)
                ),
                true
            );
            
            return true;
        } else {
            return false;
        }
    }
}
