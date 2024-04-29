<?php

/**
 * @category Integration
 * @package  Intaro\RetailCrm\Model\Api\Request\Filter
 * @author   RetailCRM <integration@retailcrm.ru>
 * @license  MIT
 * @link     http://retailcrm.ru
 * @see      http://retailcrm.ru/docs
 */

namespace Intaro\RetailCrm\Model\Api\Request\Filter;

use Intaro\RetailCrm\Component\Json\Mapping;
use Intaro\RetailCrm\Model\Api\AbstractApiModel;

/**
 * Class CustomersCorporateFilter
 *
 * @package Intaro\RetailCrm\Model\Api\Request\Filter
 */
class CustomersCorporateFilter extends AbstractApiModel
{
    /**
     * @var string[]
     *
     * @Mapping\Type("string[]")
     * @Mapping\SerializedName("nickName")
     */
    public $nickName;

    /**
     * @var string
     *
     * @Mapping\Type("string")
     * @Mapping\SerializedName("contactName")
     */
    public $contactName;

    /**
     * @var string
     *
     * @Mapping\Type("string")
     * @Mapping\SerializedName("contragentName")
     */
    public $contragentName;

    /**
     * @var string[]
     *
     * @Mapping\Type("string[]")
     * @Mapping\SerializedName("contragentTypes")
     */
    public $contragentTypes;

    /**
     * @var string
     *
     * @Mapping\Type("string")
     * @Mapping\SerializedName("contragentInn")
     */
    public $contragentInn;

    /**
     * @var string
     *
     * @Mapping\Type("string")
     * @Mapping\SerializedName("contragentKpp")
     */
    public $contragentKpp;

    /**
     * @var string
     *
     * @Mapping\Type("string")
     * @Mapping\SerializedName("contragentBik")
     */
    public $contragentBik;

    /**
     * @var string
     *
     * @Mapping\Type("string")
     * @Mapping\SerializedName("contragentCorrAccount")
     */
    public $contragentCorrAccount;

    /**
     * @var string
     *
     * @Mapping\Type("string")
     * @Mapping\SerializedName("contragentBankAccount")
     */
    public $contragentBankAccount;

    /**
     * @var string[]
     *
     * @Mapping\Type("string[]")
     * @Mapping\SerializedName("externalIds")
     */
    public $externalIds;

    /**
     * @var string
     *
     * @Mapping\Type("string")
     * @Mapping\SerializedName("name")
     */
    public $name;

    /**
     * @var string
     *
     * @Mapping\Type("string")
     * @Mapping\SerializedName("city")
     */
    public $city;

    /**
     * @var string
     *
     * @Mapping\Type("string")
     * @Mapping\SerializedName("region")
     */
    public $region;

    /**
     * @var string
     *
     * @Mapping\Type("string")
     * @Mapping\SerializedName("email")
     */
    public $email;

    /**
     * @var string
     *
     * @Mapping\Type("string")
     * @Mapping\SerializedName("notes")
     */
    public $notes;

    /**
     * @var string
     *
     * @Mapping\Type("string")
     * @Mapping\SerializedName("minOrdersCount")
     */
    public $minOrdersCount;

    /**
     * @var string
     *
     * @Mapping\Type("string")
     * @Mapping\SerializedName("maxOrdersCount")
     */
    public $maxOrdersCount;

    /**
     * @var string
     *
     * @Mapping\Type("string")
     * @Mapping\SerializedName("minAverageSumm")
     */
    public $minAverageSumm;

    /**
     * @var string
     *
     * @Mapping\Type("string")
     * @Mapping\SerializedName("maxAverageSumm")
     */
    public $maxAverageSumm;

    /**
     * @var string
     *
     * @Mapping\Type("string")
     * @Mapping\SerializedName("minTotalSumm")
     */
    public $minTotalSumm;

    /**
     * @var string
     *
     * @Mapping\Type("string")
     * @Mapping\SerializedName("maxTotalSumm")
     */
    public $maxTotalSumm;

    /**
     * @var string
     *
     * @Mapping\Type("string")
     * @Mapping\SerializedName("classSegment")
     */
    public $classSegment;

    /**
     * @var string
     *
     * @Mapping\Type("string")
     * @Mapping\SerializedName("discountCardNumber")
     */
    public $discountCardNumber;

    /**
     * @var string
     *
     * @Mapping\Type("string")
     * @Mapping\SerializedName("attachments")
     */
    public $attachments;

    /**
     * @var string
     *
     * @Mapping\Type("string")
     * @Mapping\SerializedName("minCostSumm")
     */
    public $minCostSumm;

    /**
     * @var string
     *
     * @Mapping\Type("string")
     * @Mapping\SerializedName("maxCostSumm")
     */
    public $maxCostSumm;

    /**
     * @var bool
     *
     * @Mapping\Type("bool")
     * @Mapping\SerializedName("vip")
     */
    public $vip;

    /**
     * @var bool
     *
     * @Mapping\Type("bool")
     * @Mapping\SerializedName("bad")
     */
    public $bad;

    /**
     * @var string
     *
     * @Mapping\Type("string")
     * @Mapping\SerializedName("tasksCounts")
     */
    public $tasksCounts;

    /**
     * @var int[]
     *
     * @Mapping\Type("int[]")
     * @Mapping\SerializedName("ids")
     */
    public $ids;

    /**
     * @var string[]
     *
     * @Mapping\Type("string[]")
     * @Mapping\SerializedName("sites")
     */
    public $sites;

    /**
     * @var int[]
     *
     * @Mapping\Type("int[]")
     * @Mapping\SerializedName("managers")
     */
    public $managers;

    /**
     * @var string[]
     *
     * @Mapping\Type("string[]")
     * @Mapping\SerializedName("managerGroups")
     */
    public $managerGroups;

    /**
     * @var string
     *
     * @Mapping\Type("string")
     * @Mapping\SerializedName("dateFrom")
     */
    public $dateFrom;

    /**
     * @var string
     *
     * @Mapping\Type("string")
     * @Mapping\SerializedName("dateTo")
     */
    public $dateTo;

    /**
     * @var string
     *
     * @Mapping\Type("string")
     * @Mapping\SerializedName("firstOrderFrom")
     */
    public $firstOrderFrom;

    /**
     * @var string
     *
     * @Mapping\Type("string")
     * @Mapping\SerializedName("firstOrderTo")
     */
    public $firstOrderTo;

    /**
     * @var string
     *
     * @Mapping\Type("string")
     * @Mapping\SerializedName("lastOrderFrom")
     */
    public $lastOrderFrom;

    /**
     * @var string
     *
     * @Mapping\Type("string")
     * @Mapping\SerializedName("lastOrderTo")
     */
    public $lastOrderTo;

    /**
     * @var array
     *
     * @Mapping\Type("array")
     * @Mapping\SerializedName("customFields")
     */
    public $customFields;

    /**
     * @var int[]
     *
     * @Mapping\Type("int[]")
     * @Mapping\SerializedName("contactIds")
     */
    public $contactIds;

    /**
     * @var string
     *
     * @Mapping\Type("string")
     * @Mapping\SerializedName("companyName")
     */
    public $companyName;
}
