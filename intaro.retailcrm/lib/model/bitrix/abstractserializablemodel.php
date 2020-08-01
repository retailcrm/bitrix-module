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
use Intaro\RetailCrm\Component\Json\Deserializer;
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
     * Should return data by provided primary key
     *
     * @param mixed $primary
     *
     * @return array
     */
    abstract public static function getDataArrayByPrimary($primary): array;

    /**
     * AbstractSerializableModel constructor.
     *
     * @param mixed $primary
     *
     * @throws \ReflectionException
     */
    public function __construct($primary = null)
    {
        if ($primary !== null) {
            $data = static::getDataArrayByPrimary($primary);

            if (!empty($data)) {
                $thisClassName = get_class($this);
                $instance = Deserializer::deserializeArray($data, $thisClassName);

                if ($instance instanceof $thisClassName) {
                    $instanceReflection = new \ReflectionClass($instance);

                    foreach ($instanceReflection->getProperties() as $property) {
                        $thisProperty = new \ReflectionProperty($thisClassName, $property->getName());
                        $property->setAccessible(true);
                        $thisProperty->setAccessible(true);
                        $thisProperty->setValue($this, $property->getValue($instance));
                    }
                }
            }
        }
    }

    /**
     * Tries to add object via base class
     *
     * @return \Bitrix\Main\ORM\Data\Result
     */
    public function add(): Result
    {
        $result = null;
        $instance = null;
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

        return $this->constructResult($instance, $result);
    }

    /**
     * Tries to save object via base class
     *
     * @return \Bitrix\Main\ORM\Data\Result
     */
    public function save(): Result
    {
        $primary = $this->getPrimaryKeyData();

        if (empty($primary)) {
            return $this->add();
        }

        $result = null;
        $instance = null;
        $data = $this->serialize();
        $baseClass = $this->getBaseClass();

        if ($this->isSaveStatic()) {
            if (method_exists($baseClass, 'Update')) {
                $result = call_user_func($baseClass . '::Update', $primary, $data);
            } elseif (method_exists($baseClass, 'update')) {
                $result = call_user_func($baseClass . '::update', $primary, $data);
            }
        } else {
            $instance = new $baseClass();

            if (method_exists($instance, 'Update')) {
                $result = $instance->Update($primary, $data);
            } elseif (method_exists($instance, 'update')) {
                $result = $instance->update($primary, $data);
            }
        }

        if (null === $result) {
            throw new \RuntimeException(
                "Neither Add(\$data) nor add(\$data) is exist in the base class or it's instance"
            );
        }

        return $this->constructResult($instance, $result);
    }

    /**
     * Tries to delete object via base class
     *
     * @return \Bitrix\Main\ORM\Data\Result
     */
    public function delete(): Result
    {
        $result = null;
        $instance = null;
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

        return $this->constructResult($instance, $result);
    }

    /**
     * @param mixed $baseClassInstance
     * @param mixed $result
     *
     * @return \Bitrix\Main\ORM\Data\Result
     */
    private function constructResult($baseClassInstance, $result): Result
    {
        $newResult = new Result();

        if ($result instanceof \CDBResult && !$result->AffectedRowsCount()) {
            $newResult->addError(new Error('No rows were affected.'));
        }

        if (is_int($result)) {
            if ($result > 0) {
                $this->setPrimaryKeyData($result);
            } else {
                $newResult->addError(new Error('Entity is not saved - no primary key returned.'));
            }
        }

        if (is_object($baseClassInstance)
            && property_exists($baseClassInstance, 'LAST_ERROR')
            && !empty($baseClassInstance->LAST_ERROR)
        ) {
            $newResult->addError(new Error($baseClassInstance->LAST_ERROR));
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
     * Tries to set primary key data
     *
     * @param mixed $primaryData
     *
     * @return mixed
     */
    private function setPrimaryKeyData($primaryData)
    {
        if (method_exists($this, 'setId')) {
            return $this->setId($primaryData);
        } elseif (method_exists($this, 'setPrimary')) {
            return $this->setPrimary($primaryData);
        } else {
            throw new \RuntimeException('AbstractSerializableModel child should implement setId or setPrimary');
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
