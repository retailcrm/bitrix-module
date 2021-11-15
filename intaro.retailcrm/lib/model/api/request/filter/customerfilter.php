<?php

/**
 * PHP version 7.1
 *
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
 * Class CustomerFilter
 *
 * @package Intaro\RetailCrm\Model\Api\Request\Filter
 */
class CustomerFilter extends AbstractApiModel
{
    /**
     * @var bool
     *
     * @Mapping\Type("bool")
     * @Mapping\SerializedName("isContact")
     */
    public $isContact;

    /**
     * @var string
     *
     * @Mapping\Type("string")
     * @Mapping\SerializedName("online")
     */
    public $online;

    /**
     * @var string
     *
     * @Mapping\Type("string")
     * @Mapping\SerializedName("sex")
     */
    public $sex;

    /**
     * @var bool
     *
     * @Mapping\Type("bool")
     * @Mapping\SerializedName("emailMarketingUnsubscribed")
     */
    public $emailMarketingUnsubscribed;

    /**
     * @var string[]
     *
     * @Mapping\Type("string[]")
     * @Mapping\SerializedName("countries")
     */
    public $countries;

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
     * @var string
     *
     * @Mapping\Type("string")
     * @Mapping\SerializedName("segment")
     */
    public $segment;

    /**
     * @var int[]
     *
     * @Mapping\Type("int[]")
     * @Mapping\SerializedName("mgChannels")
     */
    public $mgChannels;

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
     * @Mapping\SerializedName("firstWebVisitFrom")
     */
    public $firstWebVisitFrom;

    /**
     * @var string
     *
     * @Mapping\Type("string")
     * @Mapping\SerializedName("firstWebVisitTo")
     */
    public $firstWebVisitTo;

    /**
     * @var string
     *
     * @Mapping\Type("string")
     * @Mapping\SerializedName("lastWebVisitFrom")
     */
    public $lastWebVisitFrom;

    /**
     * @var string
     *
     * @Mapping\Type("string")
     * @Mapping\SerializedName("lastWebVisitTo")
     */
    public $lastWebVisitTo;

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
     * @var string
     *
     * @Mapping\Type("string")
     * @Mapping\SerializedName("browserId")
     */
    public $browserId;

    /**
     * @var string
     *
     * @Mapping\Type("string")
     * @Mapping\SerializedName("commentary")
     */
    public $commentary;

    /**
     * @var string
     *
     * @Mapping\Type("string")
     * @Mapping\SerializedName("sourceName")
     */
    public $sourceName;

    /**
     * @var string
     *
     * @Mapping\Type("string")
     * @Mapping\SerializedName("mediumName")
     */
    public $mediumName;

    /**
     * @var string
     *
     * @Mapping\Type("string")
     * @Mapping\SerializedName("campaignName")
     */
    public $campaignName;

    /**
     * @var string
     *
     * @Mapping\Type("string")
     * @Mapping\SerializedName("keywordName")
     */
    public $keywordName;

    /**
     * @var string
     *
     * @Mapping\Type("string")
     * @Mapping\SerializedName("adContentName")
     */
    public $adContentName;

    /**
     * @var string
     *
     * @Mapping\Type("string")
     * @Mapping\SerializedName("mgCustomerId")
     */
    public $mgCustomerId;

    /**
     * @var string[]
     *
     * @Mapping\Type("string[]")
     * @Mapping\SerializedName("tags")
     */
    public $tags;

    /**
     * @var string[]
     *
     * @Mapping\Type("string[]")
     * @Mapping\SerializedName("attachedTags")
     */
    public $attachedTags;
}
