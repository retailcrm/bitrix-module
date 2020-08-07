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

use Intaro\RetailCrm\Component\Json\Mapping\NoTransform;

/**
 * Class IsNoTransformTrait
 *
 * @package Intaro\RetailCrm\Component\Json\Strategy
 */
trait IsNoTransformTrait
{
    use AnnotationReaderTrait;

    /**
     * Returns true if NoTransform annotation was used
     *
     * @param \ReflectionProperty $property
     *
     * @return bool
     */
    protected static function isNoTransform(\ReflectionProperty $property): bool
    {
        $isNoTransform = static::annotationReader()->getPropertyAnnotation($property, NoTransform::class);

        return $isNoTransform instanceof NoTransform;
    }
}
