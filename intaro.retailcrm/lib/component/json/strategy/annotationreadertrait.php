<?php
/**
 * PHP version 7.1
 *
 * @category Integration
 * @package  Intaro\RetailCrm\Component\Json\Strategy
 * @author   RetailCRM <integration@retailcrm.ru>
 * @license  MIT
 * @link     http://retailcrm.ru
 * @see      http://retailcrm.ru/docs
 */
namespace Intaro\RetailCrm\Component\Json\Strategy;

use Intaro\RetailCrm\Component\ServiceLocator;
use Intaro\RetailCrm\Vendor\Doctrine\Common\Annotations\AnnotationReader;

/**
 * Trait AnnotationReaderTrait
 *
 * @package Intaro\RetailCrm\Component\Json
 */
trait AnnotationReaderTrait
{
    /**
     * @return \Intaro\RetailCrm\Vendor\Doctrine\Common\Annotations\AnnotationReader
     */
    private static function annotationReader()
    {
        return ServiceLocator::get(AnnotationReader::class);
    }
}
