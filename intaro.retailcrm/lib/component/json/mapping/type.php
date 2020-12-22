<?php
/**
 * PHP version 7.1
 *
 * @category Integration
 * @package  Intaro\RetailCrm\Component\Json\Mapping
 * @author   RetailCRM <integration@retailcrm.ru>
 * @license  MIT
 * @link     http://retailcrm.ru
 * @see      http://retailcrm.ru/docs
 */
namespace Intaro\RetailCrm\Component\Json\Mapping;

use Intaro\RetailCrm\Vendor\Doctrine\Common\Annotations\Annotation;
use Intaro\RetailCrm\Vendor\Doctrine\Common\Annotations\Annotation\Target;
use Intaro\RetailCrm\Vendor\Doctrine\Common\Annotations\Annotation\Attribute;
use Intaro\RetailCrm\Vendor\Doctrine\Common\Annotations\Annotation\Attributes;

/**
 * Class Type
 *
 * @package Intaro\RetailCrm\Component\Json\Mapping
 * @Annotation
 * @Attributes(
 *     @Attribute("type", required=false, type="string")
 * )
 * @Target({"PROPERTY","ANNOTATION"})
 */
final class Type
{
    /**
     * Property type
     *
     * @var string
     */
    public $type;
}
