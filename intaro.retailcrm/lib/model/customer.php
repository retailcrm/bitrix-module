<?php

/**
 * PHP version 7.1
 *
 * @category Integration
 * @package  Intaro\RetailCrm\Model
 * @author   retailCRM <integration@retailcrm.ru>
 * @license  MIT
 * @link     http://retailcrm.ru
 * @see      http://retailcrm.ru/docs
 */
namespace Intaro\RetailCrm\Model;

use Intaro\RetailCrm\Component\Json\Mapping\Accessor;
use Intaro\RetailCrm\Component\Json\Mapping\Name;

/**
 * Class Customer
 * TODO: Create necessary models for retailCRM entities
 *
 * @package Intaro\RetailCrm\Model
 */
class Customer
{
    /**
     * @var int
     */
    private $id;

    /**
     * @var string
     */
    private $externalId;

    /**
     * @var bool
     */
    private $isContact = false;

    /**
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * @param int $id
     *
     * @return Customer
     */
    public function setId(int $id): Customer
    {
        $this->id = $id;
        return $this;
    }

    /**
     * @return string
     */
    public function getExternalId(): string
    {
        return $this->externalId;
    }

    /**
     * @param string $externalId
     *
     * @return Customer
     */
    public function setExternalId(string $externalId): Customer
    {
        $this->externalId = $externalId;
        return $this;
    }

    /**
     * @return bool
     */
    public function isContact(): bool
    {
        return $this->isContact;
    }

    /**
     * @param bool $isContact
     *
     * @return Customer
     */
    public function setIsContact(bool $isContact): Customer
    {
        $this->isContact = $isContact;
        return $this;
    }
}
