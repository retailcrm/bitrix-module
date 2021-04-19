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

/**
 * Class NoTransform
 *
 * @package Intaro\RetailCrm\Component\Json\Mapping
 * @Annotation
 * @Target({"PROPERTY","ANNOTATION"})
 */
final class NoTransform
{
}
