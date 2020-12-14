<?php
/**
 * PHP version 7.1
 *
 * @category Integration
 * @package  Intaro\RetailCrm\Component\Json
 * @author   retailCRM <integration@retailcrm.ru>
 * @license  MIT
 * @link     http://retailcrm.ru
 * @see      http://retailcrm.ru/docs
 */
namespace Intaro\RetailCrm\Component\Json;

use Exception;
use RuntimeException;

/**
 * Ответы для контроллеров
 *
 * Class Response
 * @package Intaro\RetailCrm\Component\Json
 */
class Response
{
    /**
     * @param int    $codeStatus
     * @param array  $data
     * @param string $errMsg
     * @return array
     */
    public function set(int $codeStatus, array $data, string $errMsg): array
    {
        http_response_code($codeStatus);
        
        if (isset($errMsg)) {
            throw new RuntimeException($errMsg);
        }
        
        return $data;
    }
}
