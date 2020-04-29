<?php

IncludeModuleLangFile(__FILE__);

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
