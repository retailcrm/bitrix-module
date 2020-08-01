<?php
/**
 * PHP version 7.1
 *
 * @category Integration
 * @package  Intaro\RetailCrm\Model\Bitrix
 * @author   retailCRM <integration@retailcrm.ru>
 * @license  MIT
 * @link     http://retailcrm.ru
 * @see      http://retailcrm.ru/docs
 */
namespace Intaro\RetailCrm\Model\Bitrix;

use Intaro\RetailCrm\Component\Json\Mapping;

/**
 * Class BuyerProfile
 *
 * @package Intaro\RetailCrm\Model\Bitrix
 */
class BuyerProfile extends AbstractSerializableModel
{
    /**
     * @var int
     *
     * @Mapping\Type("int")
     * @Mapping\SerializedName("ID")
     */
    protected $id;

    /**
     * @var string
     *
     * @Mapping\Type("string")
     * @Mapping\SerializedName("NAME")
     */
    protected $name;

    /**
     * @var string
     *
     * @Mapping\Type("string")
     * @Mapping\SerializedName("USER_ID")
     */
    protected $userId;

    /**
     * @var string
     *
     * @Mapping\Type("string")
     * @Mapping\SerializedName("PERSON_TYPE_ID")
     */
    protected $personTypeId;

    /**
     * @return int|null
     */
    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * @param int $id
     *
     * @return BuyerProfile
     */
    public function setId(int $id): BuyerProfile
    {
        $this->id = $id;
        return $this;
    }

    /**
     * @return string
     */
    public function getName(): ?string
    {
        return $this->name;
    }

    /**
     * @param string $name
     *
     * @return BuyerProfile
     */
    public function setName(string $name): BuyerProfile
    {
        $this->name = $name;
        return $this;
    }

    /**
     * @return string
     */
    public function getUserId(): ?string
    {
        return $this->userId;
    }

    /**
     * @param string $userId
     *
     * @return BuyerProfile
     */
    public function setUserId(string $userId): BuyerProfile
    {
        $this->userId = $userId;
        return $this;
    }

    /**
     * @return string
     */
    public function getPersonTypeId(): ?string
    {
        return $this->personTypeId;
    }

    /**
     * @param string $personTypeId
     *
     * @return BuyerProfile
     */
    public function setPersonTypeId(string $personTypeId): BuyerProfile
    {
        $this->personTypeId = $personTypeId;
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function getBaseClass(): string
    {
        return \CSaleOrderUserProps::class;
    }

    /**
     * @inheritDoc
     */
    public function isSaveStatic(): bool
    {
        return false;
    }

    /**
     * @inheritDoc
     */
    public function isDeleteStatic(): bool
    {
        return false;
    }
}
