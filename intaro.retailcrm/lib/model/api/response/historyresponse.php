<?php
/**
 * PHP version 7.1
 *
 * @category Integration
 * @package  Intaro\RetailCrm\Model\Api\Response
 * @author   retailCRM <integration@retailcrm.ru>
 * @license  MIT
 * @link     http://retailcrm.ru
 * @see      http://retailcrm.ru/docs
 */
namespace Intaro\RetailCrm\Model\Api\Response;

use Intaro\RetailCrm\Component\Json\Mapping;

/**
 * Class HistoryResponse
 *
 * @package Intaro\RetailCrm\Model\Api
 */
class HistoryResponse extends OperationResponse
{
    /**
     * @var \Intaro\RetailCrm\Model\Api\History[]
     *
     * @Mapping\Type("int")
     * @Mapping\SerializedName("Intaro\RetailCrm\Model\Api\History[]")
     */
    public $history;
}
