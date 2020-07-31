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
     * Returns true if Add method should be called from class itself, not it's instance.
     *
     * @return bool
     */
    abstract public function isSaveStatic(): bool;

    /**
     * Returns true if Delete method should be called from class itself, not it's instance.
     *
     * @return bool
     */
    abstract public function isDeleteStatic(): bool;

    /**
     * Tries to save object via base class
     *
     * @return \Bitrix\Main\ORM\Data\Result
     */
    public function save(): Result
    {
        $result = null;
        $data = $this->serialize();
        $baseClass = $this->getBaseClass();

        if ($this->isSaveStatic()) {
            if (method_exists($baseClass, 'Add')) {
                $result = call_user_func($baseClass . '::Add', $data);
            } elseif (method_exists($baseClass, 'add')) {
                $result = call_user_func($baseClass . '::add', $data);
            }
        } else {
            $instance = new $baseClass();

            if (method_exists($instance, 'Add')) {
                $result = $instance->Add($data);
            } elseif (method_exists($instance, 'add')) {
                $result = $instance->add($data);
            }
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
        $result = null;
        $baseClass = $this->getBaseClass();
        $primary = $this->getPrimaryKeyData();

        if ($this->isDeleteStatic()) {
            if (method_exists($baseClass, 'Delete')) {
                $result = call_user_func($baseClass . '::Delete', $primary);
            } elseif (method_exists($baseClass, 'delete')) {
                $result = call_user_func($baseClass . '::delete', $primary);
            }
        } else {
            $instance = new $baseClass();

            if (method_exists($instance, 'Delete')) {
                $result = $instance->Delete($primary);
            } elseif (method_exists($instance, 'delete')) {
                $result = $instance->delete($primary);
            }
        }

        if (null === $result) {
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
