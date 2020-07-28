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

use Intaro\RetailCrm\Component\Doctrine\Common\Annotations\Annotation;
use Intaro\RetailCrm\Component\Doctrine\Common\Annotations\Annotation\Target;
use Intaro\RetailCrm\Component\Doctrine\Common\Annotations\Annotation\Attribute;
use Intaro\RetailCrm\Component\Doctrine\Common\Annotations\Annotation\Attributes;

/**
 * Class Accessor
 *
 * @package Intaro\RetailCrm\Component\Json\Mapping
 * @Annotation
 * @Attributes(
 *     @Attribute("getter", required=false, type="string"),
 *     @Attribute("setter", required=false, type="string")
 * )
 * @Target({"PROPERTY","ANNOTATION"})
 */
final class Accessor
{
    /**
     * Property getter
     *
     * @var string
     */
    public $getter;

    /**
     * Property setter
     *
     * @var string
     */
    public $setter;
}
