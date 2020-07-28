<?php

/**
 * PHP version 7.1
 *
 * @category Integration
 * @package  Intaro\RetailCrm\Component\Json\Strategy
 * @author   retailCRM <integration@retailcrm.ru>
 * @license  MIT
 * @link     http://retailcrm.ru
 * @see      http://retailcrm.ru/docs
 */
namespace Intaro\RetailCrm\Component\Json\Strategy;

use Intaro\RetailCrm\Component\Doctrine\Common\Annotations\AnnotationReader;

/**
 * Trait AnnotationReaderTrait
 *
 * @package Intaro\RetailCrm\Component\Json
 */
trait AnnotationReaderTrait
{
    /** @var \Intaro\RetailCrm\Component\Doctrine\Common\Annotations\AnnotationReader */
    private static $_annotationReader;

    /**
     * @return \Intaro\RetailCrm\Component\Doctrine\Common\Annotations\AnnotationReader
     */
    private static function annotationReader(): AnnotationReader
    {
        if (empty(static::$_annotationReader)) {
            static::$_annotationReader = new AnnotationReader();
        }

        return static::$_annotationReader;
    }
}
