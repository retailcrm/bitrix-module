<?php
/**
 * PHP version 7.1
 *
 * @category Integration
 * @package  Intaro\RetailCrm\Component\Builder
 * @author   retailCRM <integration@retailcrm.ru>
 * @license  MIT
 * @link     http://retailcrm.ru
 * @see      http://retailcrm.ru/docs
 */
namespace Intaro\RetailCrm\Component\Builder;

/**
 * Interface BuilderInterface
 *
 * @package Intaro\RetailCrm\Component\Builder
 */
interface BuilderInterface
{
    /**
     * Builds result
     *
     * @return mixed
     */
    public function build(): BuilderInterface;

    /**
     * Returns builder result
     *
     * @return mixed
     */
    public function getResult();
}
