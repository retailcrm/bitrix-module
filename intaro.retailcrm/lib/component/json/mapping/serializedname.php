<?php
/**
 * PHP version 7.1
 *
 * @category Integration
 * @package  Intaro\RetailCrm\Component\Json\Mapping
 * @author   retailCRM <integration@retailcrm.ru>
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
 * Class SerializedName
 *
 * @package Intaro\RetailCrm\Component\Json\Mapping
 * @Annotation
 * @Attributes(
 *     @Attribute("name", required=true, type="string")
 * )
 * @Target({"PROPERTY","ANNOTATION"})
 */
final class SerializedName
{
    /**
     * Property name in result JSON
     *
     * @var string
     */
    public $name;
}
