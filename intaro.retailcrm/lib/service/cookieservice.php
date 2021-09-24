<?php

/**
 * PHP version 7.1
 *
 * @category Integration
 * @package  Intaro\RetailCrm\Service
 * @author   RetailCRM <integration@retailcrm.ru>
 * @license  MIT
 * @link     http://retailcrm.ru
 * @see      http://retailcrm.ru/docs
 */

namespace Intaro\RetailCrm\Service;

use Bitrix\Main\Application;
use Bitrix\Main\Context;
use Bitrix\Main\SystemException;
use Bitrix\Main\Web\Cookie;
use Exception;
use Intaro\RetailCrm\Component\Json\Deserializer;
use Intaro\RetailCrm\Component\Json\Serializer;
use Intaro\RetailCrm\Model\Api\SmsVerification;
use Intaro\RetailCrm\Model\Bitrix\SmsCookie;
use Logger;

/**
 * Class CookieService
 *
 * @package Intaro\RetailCrm\Service
 */
class CookieService
{
    /**
     * Extracts daemon collector cookie if it's present.
     *
     * @return string|null
     */
    public function extractCookie(): ?string
    {
        global $_COOKIE;

        return (isset($_COOKIE['_rc']) && $_COOKIE['_rc'] != '') ? $_COOKIE['_rc'] : null;
    }

    /**
     * Получает десерализованное содержимое куки
     *
     * @param string $cookieName
     * @return \Intaro\RetailCrm\Model\Bitrix\SmsCookie|null
     */
    public function getSmsCookie(string $cookieName): ?SmsCookie
    {
        try {
            $application = Application::getInstance();

            if ($application === null) {
                return null;
            }

            $cookieJson = $application->getContext()->getRequest()->getCookie($cookieName);

            if ($cookieJson !== null) {
                return Deserializer::deserialize($cookieJson, SmsCookie::class);
            }
        } catch (SystemException | Exception $exception) {
            Logger::getInstance()->write($exception->getMessage());
        }

        return null;
    }

    /**
     * @param string                                      $cookieName
     * @param \Intaro\RetailCrm\Model\Api\SmsVerification $smsVerification
     *
     * @return \Intaro\RetailCrm\Model\Bitrix\SmsCookie
     * @throws \ReflectionException
     */
    public function setSmsCookie(string $cookieName, SmsVerification $smsVerification): SmsCookie
    {
        $resendAvailable = $smsVerification->createdAt->modify('+1 minutes');

        $smsCookie                  = new SmsCookie();
        $smsCookie->createdAt       = $smsVerification->createdAt;
        $smsCookie->resendAvailable = $resendAvailable;
        $smsCookie->isVerified      = !empty($smsVerification->verifiedAt);
        $smsCookie->expiredAt       = $smsVerification->expiredAt;
        $smsCookie->checkId         = $smsVerification->checkId;

        $serializedArray = Serializer::serialize($smsCookie);

        $cookie = new Cookie(
            $cookieName,
            $serializedArray,
            MakeTimeStamp(
                $smsVerification->expiredAt->format('Y-m-d H:i:s'),
                'YYYY.MM.DD HH:MI:SS'
            )
        );

        Context::getCurrent()->getResponse()->addCookie($cookie);

        return $smsCookie;
    }
}
