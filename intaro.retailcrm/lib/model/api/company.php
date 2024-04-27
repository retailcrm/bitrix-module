<?php

/**
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
 * Class Company
 *
 * @package Intaro\RetailCrm\Model\Api
 */
class Company extends AbstractApiModel
{
    /**
     * ID компании
     *
     * @var integer $id
     *
     * @Mapping\Type("integer")
     * @Mapping\SerializedName("id")
     */
    public $id;

    /**
     * Внешний идентификатор компании в складской системе
     *
     * @var string $uuid
     *
     * @Mapping\Type("string")
     * @Mapping\SerializedName("uuid")
     */
    public $uuid;

    /**
     * Главная компания
     *
     * @var boolean $isMain
     *
     * @Mapping\Type("boolean")
     * @Mapping\SerializedName("isMain")
     */
    public $isMain;

    /**
     * Магазин
     *
     * @var string $site
     *
     * @Mapping\Type("string")
     * @Mapping\SerializedName("site")
     */
    public $site;

    /**
     * Внешний ID компании
     *
     * @var string $externalId
     *
     * @Mapping\Type("string")
     * @Mapping\SerializedName("externalId")
     */
    public $externalId;

    /**
     * Активность
     *
     * @var string $active
     *
     * @Mapping\Type("string")
     * @Mapping\SerializedName("active")
     */
    public $active;

    /**
     * Наименование
     *
     * @var string $name
     *
     * @Mapping\Type("string")
     * @Mapping\SerializedName("name")
     */
    public $name;

    /**
     * Бренд
     *
     * @var string $brand
     *
     * @Mapping\Type("string")
     * @Mapping\SerializedName("brand")
     */
    public $brand;

    /**
     * Дата создания
     *
     * @var \DateTime $createdAt
     *
     * @Mapping\Type("DateTime<'Y-m-d H:i:s'>")
     * @Mapping\SerializedName("createdAt")
     */
    public $createdAt;

    /**
     * Реквизиты
     *
     * @var Contragent $contragent
     *
     * @Mapping\Type("Intaro\RetailCrm\Model\Api\Contragent")
     * @Mapping\SerializedName("contragent")
     */
    public $contragent;

    /**
     * Адрес
     *
     * @var Address $address
     *
     * @Mapping\Type("Intaro\RetailCrm\Model\Api\Address")
     * @Mapping\SerializedName("address")
     */
    public $address;

    /**
     * Ассоциативный массив пользовательских полей
     *
     * @var array $customFields
     *
     * @Mapping\Type("array")
     * @Mapping\SerializedName("customFields")
     */
    public $customFields;
}
