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
 * Class History
 *
 * @package Intaro\RetailCrm\Model\Api
 */
class History extends AbstractApiModel
{
    /**
     * Внутренний идентификатор записи в истории
     *
     * @var integer $id
     *
     * @Mapping\Type("integer")
     * @Mapping\SerializedName("id")
     */
    public $id;

    /**
     * Дата внесения изменения
     *
     * @var \DateTime $createdAt
     *
     * @Mapping\Type("DateTime<'Y-m-d H:i:s'>")
     * @Mapping\SerializedName("createdAt")
     */
    public $createdAt;

    /**
     * Признак создания сущности
     *
     * @var string $created
     *
     * @Mapping\Type("boolean")
     * @Mapping\SerializedName("created")
     */
    public $created;

    /**
     * Признак удаления сущности
     *
     * @var string $deleted
     *
     * @Mapping\Type("boolean")
     * @Mapping\SerializedName("deleted")
     */
    public $deleted;

    /**
     * Источник изменения
     *
     * @var string $source
     *
     * @Mapping\Type("string")
     * @Mapping\SerializedName("source")
     */
    public $source;

    /**
     * Пользователь
     *
     * @var \Intaro\RetailCrm\Model\Api\User $user
     *
     * @Mapping\Type("Intaro\RetailCrm\Model\Api\User")
     * @Mapping\SerializedName("user")
     */
    public $user;

    /**
     * Имя изменившегося поля
     *
     * @var string $field
     *
     * @Mapping\Type("string")
     * @Mapping\SerializedName("field")
     */
    public $field;

    /**
     * Информация о ключе api, использовавшемся для этого изменения
     *
     * @var array $apiKey
     *
     * @Mapping\Type("array")
     * @Mapping\SerializedName("apiKey")
     */
    public $apiKey;

    /**
     * Старое значение свойства
     *
     * @var string $oldValue
     *
     * @Mapping\Type("string")
     * @Mapping\SerializedName("oldValue")
     */
    public $oldValue;

    /**
     * Новое значение свойства
     *
     * @var string $newValue
     *
     * @Mapping\Type("string")
     * @Mapping\SerializedName("newValue")
     */
    public $newValue;

    /**
     * Клиент
     *
     * @var \Intaro\RetailCrm\Model\Api\Customer $customer
     *
     * @Mapping\Type("Intaro\RetailCrm\Model\Api\Customer")
     * @Mapping\SerializedName("customer")
     */
    public $customer;

    /**
     * Заказ
     *
     * @var \Intaro\RetailCrm\Model\Api\Order $order
     *
     * @Mapping\Type("Intaro\RetailCrm\Model\Api\Order")
     * @Mapping\SerializedName("order")
     */
    public $order;

    /**
     * Позиция в заказе
     *
     * @var \Intaro\RetailCrm\Model\Api\Item $item
     *
     * @Mapping\Type("Intaro\RetailCrm\Model\Api\Item")
     * @Mapping\SerializedName("item")
     */
    public $item;

    /**
     * Платёж
     *
     * @var \Intaro\RetailCrm\Model\Api\Payment $payment
     *
     * @Mapping\Type("Intaro\RetailCrm\Model\Api\Payment")
     * @Mapping\SerializedName("payment")
     */
    public $payment;

    /**
     * Адрес клиента
     *
     * @var \Intaro\RetailCrm\Model\Api\Address $address
     *
     * @Mapping\Type("Intaro\RetailCrm\Model\Api\Address")
     * @Mapping\SerializedName("address")
     */
    public $address;

    /**
     * Информация о [заказе|клиенте], который получился после объединения с текущим клиентом
     *
     * @var array $combinedTo
     *
     * @Mapping\Type("array")
     * @Mapping\SerializedName("combinedTo")
     */
    public $combinedTo;

    /**
     * Информация о клиенте, который получился после объединения с текущим клиентом
     *
     * @var \Intaro\RetailCrm\Model\Api\CustomerContact $customerContact
     *
     * @Mapping\Type("Intaro\RetailCrm\Model\Api\CustomerContact")
     * @Mapping\SerializedName("customerContact")
     */
    public $customerContact;

    /**
     * Информация о компании
     *
     * @var \Intaro\RetailCrm\Model\Api\Company $company
     *
     * @Mapping\Type("Intaro\RetailCrm\Model\Api\Company")
     * @Mapping\SerializedName("company")
     */
    public $company;
}
