<?php

/**
 * PHP version 7.1
 *
 * @category Integration
 * @package  Intaro\RetailCrm\Model\Api
 * @author   RetailCRM <integration@retailcrm.ru>
 * @license  MIT
 * @link     http://retailcrm.ru
 * @see      http://retailcrm.ru/docs
 */

namespace Intaro\RetailCrm\Model\Api;

/**
 * Interface ApiModelInterface
 *
 * @package Intaro\RetailCrm\Model\Api
 */
interface ApiModelInterface
{
    public function postDeserialize(): void;

    public function postSerialize(array $fields): array;
}
