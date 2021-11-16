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

use Intaro\RetailCrm\Component\Json\Mapping;

/**
 * Class PaginationResponse
 * @package Intaro\RetailCrm\Model\Api
 */
class PaginationResponse extends AbstractApiModel
{
    /**
     * Количество элементов в ответе
     *
     * @var integer $limit
     *
     * @Mapping\Type("integer")
     * @Mapping\SerializedName("limit")
     */
    public $limit;
    
    /**
     * Результат запроса (успешный/неуспешный)
     *
     * @var integer $totalCount
     *
     * @Mapping\Type("integer")
     * @Mapping\SerializedName("totalCount")
     */
    public $totalCount;
    
    /**
     * Текущая страница выдачи
     *
     * @var integer $currentPage
     *
     * @Mapping\Type("integer")
     * @Mapping\SerializedName("currentPage")
     */
    public $currentPage;
    
    /**
     * Общее количество страниц выдачи
     *
     * @var integer $totalPageCount
     *
     * @Mapping\Type("integer")
     * @Mapping\SerializedName("totalPageCount")
     */
    public $totalPageCount;
}
