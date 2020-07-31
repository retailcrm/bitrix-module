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

use Bitrix\Main\Type\DateTime;
use Intaro\RetailCrm\Component\Json\Mapping;
use Intaro\RetailCrm\Component\Json\Serializer;
use Bitrix\Main\ORM\Data\Result;
use Bitrix\Main\Error;

/**
 * Class AbstractSerializableModel
 * Contains some hacks in order to make serializable models more compatible with ORM interfaces.
 *
 * @package Intaro\RetailCrm\Model\Bitrix
 */
abstract class AbstractSerializableModel
{
    /**
     * Returns model base class
     *
     * @return string
     */
    abstract public function getBaseClass(): string;

    /**
     * Tries to save object via base class
     *
     * @return \Bitrix\Main\ORM\Data\Result
     */
    public function save(): Result
    {
        $result = null;
        $data = $this->serialize();

        if (method_exists($this->getBaseClass(), 'Add')) {
            $result = call_user_func([$this->getBaseClass(), 'Add'], $data);
        } elseif (method_exists($this->getBaseClass(), 'add')) {
            $result = call_user_func([$this->getBaseClass(), 'add'], $data);
        }

        $instance = new $this->getBaseClass();

        if (method_exists($instance, 'Add')) {
            $result = call_user_func([$this->getBaseClass(), 'Add'], $data);
        } elseif (method_exists($instance, 'add')) {
            $result = call_user_func([$this->getBaseClass(), 'add'], $data);
        }

        if (null === $result) {
            throw new \RuntimeException(
                "Neither Add(\$data) nor add(\$data) is exist in the base class or it's instance"
            );
        }

        return $this->constructResult($result);
    }

    /**
     * Tries to delete object via base class
     *
     * @return \Bitrix\Main\ORM\Data\Result
     */
    public function delete(): Result
    {
        if (method_exists($this->getBaseClass(), 'Delete')) {
            $result = call_user_func([$this->getBaseClass(), 'Delete'], $this->getPrimaryKeyData());
        } elseif (method_exists($this->getBaseClass(), 'delete')) {
            $result = call_user_func([$this->getBaseClass(), 'delete'], $this->getPrimaryKeyData());
        } else {
            throw new \RuntimeException('Neither Delete($id) nor delete($id) is exist in the base class');
        }

        return $this->constructResult($result);
    }

    /**
     * @param mixed $result
     *
     * @return \Bitrix\Main\ORM\Data\Result
     */
    private function constructResult($result): Result
    {
        $newResult = new Result();

        if ($result instanceof \CDBResult && !$result->AffectedRowsCount()) {
            $newResult->addError(new Error('No rows were affected.'));
        }

        return $newResult;
    }

    /**
     * Tries to return primary key from the model
     *
     * @return mixed
     */
    private function getPrimaryKeyData()
    {
        if (method_exists($this, 'getId')) {
            return $this->getId();
        } elseif (method_exists($this, 'getPrimary')) {
            return $this->getPrimary();
        } else {
            throw new \RuntimeException('AbstractSerializableModel child should implement getId or getPrimary');
        }
    }

    /**
     * Serializes current model
     *
     * @return array
     */
    private function serialize(): array
    {
        return Serializer::serializeArray($this);
    }
}
