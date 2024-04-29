<?php

/**
 * @category Integration
 * @package  Intaro\RetailCrm\Component\Builder
 * @author   RetailCRM <integration@retailcrm.ru>
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
     * @return \Intaro\RetailCrm\Component\Builder\BuilderInterface
     * @throws \Intaro\RetailCrm\Component\Builder\Exception\BuilderException
     */
    public function build(): BuilderInterface;

    /**
     * Resets builder
     *
     * @return \Intaro\RetailCrm\Component\Builder\BuilderInterface
     */
    public function reset(): BuilderInterface;

    /**
     * Returns builder result
     *
     * @return mixed
     */
    public function getResult();
}
