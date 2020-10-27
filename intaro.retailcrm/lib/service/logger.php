<?php

/**
 * PHP version 7.1
 *
 * @category Integration
 * @package  Intaro\RetailCrm\Service
 * @author   retailCRM <integration@retailcrm.ru>
 * @license  MIT
 * @link     http://retailcrm.ru
 * @see      http://retailcrm.ru/docs
 */
namespace Intaro\RetailCrm\Service;

use CSite;
use RuntimeException;

/**
 * Class Logger
 *
 * @author  retailCRM
 * @license https://opensource.org/licenses/MIT MIT License
 * @link    http://help.retailcrm.pro/docs/Developers
 */
class Logger
{
    /** @var self $instance */
    private static $instance;

    /** @var string $logPath */
    private $logPath;

    /** @var int $files */
    private $files;

    /**
     * Get logger instance or re-initialize it with new parameters
     *
     * @param string $logPath
     * @param int    $files
     *
     * @return \Intaro\RetailCrm\Service\Logger
     */
    public static function getInstance($logPath = '/bitrix/modules/intaro.retailcrm/log', $files = 3): Logger
    {
        if (empty(self::$instance)
            || (self::$instance instanceof self
                && (self::$instance->logPath !== $logPath || self::$instance->files !== $files))
        ) {
            self::$instance = new Logger($logPath, $files);
        }

        return self::$instance;
    }

    /**
     * Logger constructor.
     *
     * @param string $logPath
     * @param int    $files
     */
    public function __construct($logPath = '/bitrix/modules/intaro.retailcrm/log', $files = 3)
    {
        $this->logPath = $logPath;
        $this->files = $files;
    }
    
    /**
     * @param        $dump
     * @param string $file
     */
    public function write($dump, $file = 'info'): void
    {
        $rsSites = CSite::GetList($by, $sort, array('DEF' => 'Y'));
        $ar = $rsSites->Fetch();
        if (!is_dir($ar['ABS_DOC_ROOT'] . $this->logPath . '/')
            && !mkdir($concurrentDirectory = $ar['ABS_DOC_ROOT'] . $this->logPath . '/')
            && !is_dir($concurrentDirectory)) {
            throw new RuntimeException(sprintf('Directory "%s" was not created', $concurrentDirectory));
        }
        $file = $ar['ABS_DOC_ROOT'] . $this->logPath . '/' . $file . '.log';  

        $data['TIME'] = date('Y-m-d H:i:s');
        $data['DATA'] = $dump;

        $f = fopen($file, 'ab+');
        fwrite($f, print_r($data, true));
        fclose($f); 

        // if filesize more than 5 Mb rotate it
        if (filesize($file) > 5242880) {
            $this->rotate($file);
        } 
    }
    
    /**
     * @param $file
     */
    private function rotate($file): void
    {
        $path = pathinfo($file);
        $rotate = implode('', array(
            $path['dirname'],
            '/',
            $path['filename'],
            '_',
            date('Y-m-d_H:i:s'),
            '.',
            $path['extension']
        ));

        copy($file, $rotate);
        $this->clean($file);

        $files = glob($path['dirname'] . '/' . $path['filename'] . "*" . ".log");

        if (0 === $this->files) {
            return;
        }

        if (count($files) > $this->files) {
            natsort($files);
            $files = array_reverse($files);
            foreach (array_slice($files, $this->files) as $log) {
                if (is_writable($log)) {
                    unlink($log);
                }
            }
        }
    }
    
    /**
     * @param $file
     */
    private function clean($file): void
    {
        file_put_contents($file, '');
    }
}
