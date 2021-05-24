<?php

/**
 * PHP version 7.1
 *
 * @category Integration
 * @package  Intaro\RetailCrm\Component
 * @author   RetailCRM <integration@retailcrm.ru>
 * @license  MIT
 * @link     http://retailcrm.ru
 * @see      http://retailcrm.ru/docs
 */

namespace Intaro\RetailCrm\Service;

use Bitrix\Main\Entity\DataManager;
use Bitrix\Main\Loader;
use Bitrix\Main\Text\Encoding;
use Intaro\RetailCrm\Component\Constants;
use Intaro\RetailCrm\Model\Api\Response\AbstractApiResponseModel;
use Bitrix\Highloadblock as Highloadblock;
use Logger;

/**
 * Class Utils
 *
 * @package Intaro\RetailCrm\Service
 */
class Utils
{
    /**
     * Removes all empty fields from arrays, works for nested arrays
     *
     * @param array $arr
     *
     * @return array
     */
    public function clearArray(array $arr): array
    {
        if (is_array($arr) === false) {
            return $arr;
        }

        $result = array();

        foreach ($arr as $index => $node) {
            $result[$index] = is_array($node) === true ? $this->clearArray($node) : trim($node);

            if ($result[$index] == '' || $result[$index] === null || count($result[$index]) < 1) {
                unset($result[$index]);
            }
        }

        return $result;
    }

    /**
     *
     * @param array|bool|\SplFixedArray|string $string in SITE_CHARSET
     *
     * @return array|bool|\SplFixedArray|string $str in utf-8
     */
    public function toUTF8($string)
    {
        if (!defined('SITE_CHARSET')) {
            throw new \RuntimeException('SITE_CHARSET must be defined.');
        }

        return $this->convertCharset($string, SITE_CHARSET, 'utf-8');
    }

    /**
     *
     * @param string|array|\SplFixedArray $string in utf-8
     *
     * @return array|bool|\SplFixedArray|string $str in SITE_CHARSET
     */
    public function fromUTF8($string)
    {
        if (!defined('SITE_CHARSET')) {
            throw new \RuntimeException('SITE_CHARSET must be defined.');
        }

        return $this->convertCharset($string, 'utf-8', SITE_CHARSET);
    }

    /**
     * Returns true if provided PERSON_TYPE_ID is corporate customer
     *
     * @param string $personTypeId
     *
     * @return bool
     */
    public function isPersonCorporate(string $personTypeId): bool
    {
        return ConfigProvider::getContragentTypeForPersonType($personTypeId) === Constants::CORPORATE_CONTRAGENT_TYPE;
    }

    /**
     * @return string
     */
    public function createPlaceholderEmail(): string
    {
        return uniqid('user_' . time(), false) . '@example.com';
    }

    /**
     * @return string
     */
    public function createPlaceholderPassword(): string
    {
        return uniqid("R", false);
    }

    /**
     * @param string $string
     * @param string $inCharset
     * @param string $outCharset
     *
     * @return array|bool|\SplFixedArray|string
     */
    protected function convertCharset(string $string, string $inCharset, string $outCharset)
    {
        $error = '';
        $result = Encoding::convertEncoding($string,  $inCharset, $outCharset, $error);

        if (!$result && !empty($error)) {
            throw new \RuntimeException($error);
        }

        return $result;
    }
    
    /**
     * @param \Intaro\RetailCrm\Model\Api\Response\AbstractApiResponseModel $response
     *
     * @return string
     */
    public static function getResponseErrors(AbstractApiResponseModel $response): string
    {
        $errorDetails= '';
        
        foreach ($response->errors as $error) {
            $errorDetails .= $error . ' ';
        }
        
        return $errorDetails;
    }
    
    /**
     * @param \Intaro\RetailCrm\Model\Api\Response\AbstractApiResponseModel|null $response
     * @return string|null
     */
    public static function getErrorMsg(?AbstractApiResponseModel $response): ?string
    {
        if ($response !== null
            && isset($response->errorMsg)
            && !empty($response->errorMsg)
        ) {
            $errorDetails = '';
            
            if (isset($response->errors) && is_array($response->errors)) {
                $errorDetails = self::getResponseErrors($response);
            }
            
            $msg = sprintf('%s (%s %s)', GetMessage('REGISTER_ERROR'), $response->errorMsg, $errorDetails);
            
            Logger::getInstance()->write($msg);
            
            return $msg;
        }
        
        return null;
    }
    
    /**
     * @param \Intaro\RetailCrm\Model\Api\Response\AbstractApiResponseModel $response
     * @param string                                                        $errorMsg
     */
    public static function handleApiErrors(AbstractApiResponseModel $response, $errorMsg = 'ERROR')
    {
        if (isset($response->errorMsg) && !empty($response->errorMsg)) {
            $errorDetails = '';
        
            if (isset($response->errors) && is_array($response->errors)) {
                $errorDetails = self::getResponseErrors($response);
            }
        
            $msg = sprintf('%s (%s %s)', $errorMsg, $response->errorMsg, $errorDetails);
    
            Logger::getInstance()->write($msg, Constants::API_ERRORS_LOG);
        }
    }
    
    /**
     * Валидирует телефон
     *
     * @param string $phoneNumber
     * @return string|string[]|null
     */
    public static function filterPhone(string $phoneNumber)
    {
        return preg_replace('/\s|\+|-|\(|\)/', '', $phoneNumber);
    }

    /**
     * Получение DataManager класса управления HLBlock
     *
     * @param $HlBlockId
     * @return \Bitrix\Main\Entity\DataManager|null
     * @throws \Bitrix\Main\ArgumentException
     * @throws \Bitrix\Main\LoaderException
     * @throws \Bitrix\Main\ObjectPropertyException
     * @throws \Bitrix\Main\SystemException
     */
    public static function getHlClassById($HlBlockId): ?DataManager
    {
        Loader::includeModule('highloadblock');
        
        $hlblock = Highloadblock\HighloadBlockTable::getById($HlBlockId)->fetch();
        
        if (!$hlblock) {
            return null;
        }
        
        $entity = Highloadblock\HighloadBlockTable::compileEntity($hlblock);
    
        return $entity->getDataClass();
    }
    
    /**
     * Получение DataManager класса управления HLBlock
     *
     * @param $name
     * @return \Bitrix\Main\Entity\DataManager|string|null
     * @throws \Bitrix\Main\SystemException
     * @throws \Bitrix\Main\LoaderException
     */
    public static function getHlClassByName(string $name)
    {
        Loader::includeModule('highloadblock');
        
        $hlblock = Highloadblock\HighloadBlockTable::query()
            ->addSelect('*')
            ->addFilter('NAME', $name)
            ->exec()
            ->fetch();
        
        if (!$hlblock) {
            return null;
        }

        $entity = Highloadblock\HighloadBlockTable::compileEntity($hlblock);

        return $entity->getDataClass();
    }
}
