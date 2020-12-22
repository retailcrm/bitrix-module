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
 * Class Contragent
 *
 * @package Intaro\RetailCrm\Model\Api
 */
class Contragent extends AbstractApiModel
{
    /**
     * Тип контрагента
     *
     * @var string $contragentType
     *
     * @Mapping\Type("string")
     * @Mapping\SerializedName("contragentType")
     */
    public $contragentType;

    /**
     * Полное наименование
     *
     * @var string $legalName
     *
     * @Mapping\Type("string")
     * @Mapping\SerializedName("legalName")
     */
    public $legalName;

    /**
     * Адрес регистрации
     *
     * @var string $legalAddress
     *
     * @Mapping\Type("string")
     * @Mapping\SerializedName("legalAddress")
     */
    public $legalAddress;

    /**
     * ИНН
     *
     * @var string $inn
     *
     * @Mapping\Type("string")
     * @Mapping\SerializedName("INN")
     */
    public $inn;

    /**
     * КПП
     *
     * @var string $kpp
     *
     * @Mapping\Type("string")
     * @Mapping\SerializedName("KPP")
     */
    public $kpp;

    /**
     * ОКПО
     *
     * @var string $okpo
     *
     * @Mapping\Type("string")
     * @Mapping\SerializedName("OKPO")
     */
    public $okpo;

    /**
     * ОГРН
     *
     * @var string $ogrn
     *
     * @Mapping\Type("string")
     * @Mapping\SerializedName("OGRN")
     */
    public $ogrn;

    /**
     * ОГРНИП
     *
     * @var string $ogrnip
     *
     * @Mapping\Type("string")
     * @Mapping\SerializedName("OGRNIP")
     */
    public $ogrnip;

    /**
     * Номер свидетельства
     *
     * @var string $certificateNumber
     *
     * @Mapping\Type("string")
     * @Mapping\SerializedName("certificateNumber")
     */
    public $certificateNumber;

    /**
     * Дата свидетельства
     *
     * @var \DateTime $certificateDate
     *
     * @Mapping\Type("DateTime<'Y-m-d'>")
     * @Mapping\SerializedName("certificateDate")
     */
    public $certificateDate;

    /**
     * БИК
     *
     * @var string $bik
     *
     * @Mapping\Type("string")
     * @Mapping\SerializedName("BIK")
     */
    public $bik;

    /**
     * Банк
     *
     * @var string $bank
     *
     * @Mapping\Type("string")
     * @Mapping\SerializedName("bank")
     */
    public $bank;

    /**
     * Адрес банка
     *
     * @var string $bankAddress
     *
     * @Mapping\Type("string")
     * @Mapping\SerializedName("bankAddress")
     */
    public $bankAddress;

    /**
     * Корр. счёт
     *
     * @var string $corrAccount
     *
     * @Mapping\Type("string")
     * @Mapping\SerializedName("corrAccount")
     */
    public $corrAccount;

    /**
     * Расчётный счёт
     *
     * @var string $bankAccount
     *
     * @Mapping\Type("string")
     * @Mapping\SerializedName("bankAccount")
     */
    public $bankAccount;
}
