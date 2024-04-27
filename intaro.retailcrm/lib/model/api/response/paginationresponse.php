<?php

/**
 * @category Integration
 * @package  Intaro\RetailCrm\Model\Api\Response
 * @author   RetailCRM <integration@retailcrm.ru>
 * @license  MIT
 * @link     http://retailcrm.ru
 * @see      http://retailcrm.ru/docs
 */

namespace Intaro\RetailCrm\Model\Api\Response;

use Intaro\RetailCrm\Component\Json\Mapping;

/**
 * Class PaginationResponse
 *
 * @package Intaro\RetailCrm\Model\Api
 */
class PaginationResponse extends AbstractApiResponseModel
{
    /**
     * @var int
     *
     * @Mapping\Type("int")
     * @Mapping\SerializedName("limit")
     */
    public $limit;

    /**
     * @var int
     *
     * @Mapping\Type("int")
     * @Mapping\SerializedName("totalCount")
     */
    public $totalCount;

    /**
     * @var int
     *
     * @Mapping\Type("int")
     * @Mapping\SerializedName("currentPage")
     */
    public $currentPage;

    /**
     * @var int
     *
     * @Mapping\Type("int")
     * @Mapping\SerializedName("totalPageCount")
     */
    public $totalPageCount;
}
