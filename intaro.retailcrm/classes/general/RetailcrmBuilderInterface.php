<?php

/**
 * @category RetailCRM
 * @package  RetailCRM
 * @author   RetailCRM <integration@retailcrm.ru>
 * @license  MIT
 * @link     http://retailcrm.ru
 * @see      http://retailcrm.ru/docs
 */

IncludeModuleLangFile(__FILE__);

/**
 * Interface RetailcrmBuilderInterface
 *
 * @category RetailCRM
 * @package RetailCRM
 */
interface RetailcrmBuilderInterface
{
    /**
     * Set data array customerHistory
     *
     * @param array $dataCrm
     *
     * @return RetailcrmBuilderInterface
     */
    public function setDataCrm($dataCrm);

    /**
     * Build result
     */
    public function build();
}
