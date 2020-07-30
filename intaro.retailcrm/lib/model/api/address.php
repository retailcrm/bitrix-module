<?php
/**
 * PHP version 7.1
 *
 * @category Integration
 * @package  Intaro\RetailCrm\Model\Api
 * @author   retailCRM <integration@retailcrm.ru>
 * @license  MIT
 * @link     http://retailcrm.ru
 * @see      http://retailcrm.ru/docs
 */
namespace Intaro\RetailCrm\Model\Api;

use Intaro\RetailCrm\Component\Json\Mapping;


/**
 * Class Address
 *
 * @package Intaro\RetailCrm\Model\Api
 */
class Address extends AbstractApiModel
{
    /**
     * ID адреса клиента
     *
     * @var integer $id
     *
     * @Mapping\Type("integer")
     * @Mapping\SerializedName("id")
     */
    public $id;

    /**
     * Внешний ID
     *
     * @var string $externalId
     *
     * @Mapping\Type("string")
     * @Mapping\SerializedName("externalId")
     */
    public $externalId;

    /**
     * Наименование адреса
     *
     * @var string $name
     *
     * @Mapping\Type("string")
     * @Mapping\SerializedName("name")
     */
    public $name;

    /**
     * Адрес клиента является основным
     *
     * @var boolean $isMain
     *
     * @Mapping\Type("boolean")
     * @Mapping\SerializedName("isMain")
     */
    public $isMain;

    /**
     * Индекс
     *
     * @var string $index
     *
     * @Mapping\Type("string")
     * @Mapping\SerializedName("index")
     */
    public $index;

    /**
     * Город
     *
     * @var string $city
     *
     * @Mapping\Type("string")
     * @Mapping\SerializedName("city")
     */
    public $city;

    /**
     * Регион
     *
     * @var string $region
     *
     * @Mapping\Type("string")
     * @Mapping\SerializedName("region")
     */
    public $region;

    /**
     * Улица
     *
     * @var string $street
     *
     * @Mapping\Type("string")
     * @Mapping\SerializedName("street")
     */
    public $street;

    /**
     * Дом
     *
     * @var string $building
     *
     * @Mapping\Type("string")
     * @Mapping\SerializedName("building")
     */
    public $building;

    /**
     * Номер квартиры/офиса
     *
     * @var string $flat
     *
     * @Mapping\Type("string")
     * @Mapping\SerializedName("flat")
     */
    public $flat;

    /**
     * Этаж
     *
     * @var string $floor
     *
     * @Mapping\Type("string")
     * @Mapping\SerializedName("floor")
     */
    public $floor;

    /**
     * Подъезд
     *
     * @var string $block
     *
     * @Mapping\Type("string")
     * @Mapping\SerializedName("block")
     */
    public $block;

    /**
     * Строение
     *
     * @var string $house
     *
     * @Mapping\Type("string")
     * @Mapping\SerializedName("house")
     */
    public $house;

    /**
     * Корпус
     *
     * @var string $housing
     *
     * @Mapping\Type("string")
     * @Mapping\SerializedName("housing")
     */
    public $housing;

    /**
     * Метро
     *
     * @var string $metro
     *
     * @Mapping\Type("string")
     * @Mapping\SerializedName("metro")
     */
    public $metro;

    /**
     * Заметка
     *
     * @var string $notes
     *
     * @Mapping\Type("string")
     * @Mapping\SerializedName("notes")
     */
    public $notes;

    /**
     * Адрес в текстовом виде
     *
     * @var string $text
     *
     * @Mapping\Type("string")
     * @Mapping\SerializedName("text")
     */
    public $text;

    /**
     * @var string
     *
     * @Mapping\Type("string")
     * @Mapping\SerializedName("country")
     */
    public $country;

    /**
     * @var string
     *
     * @Mapping\Type("string")
     * @Mapping\SerializedName("intercomCode")
     */
    public $intercomCode;
}
