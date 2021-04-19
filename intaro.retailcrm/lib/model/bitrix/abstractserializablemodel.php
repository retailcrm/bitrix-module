<?php

/**
 * PHP version 7.1
 *
 * @category Integration
 * @package  Intaro\RetailCrm\Model\Bitrix
 * @author   RetailCRM <integration@retailcrm.ru>
 * @license  MIT
 * @link     http://retailcrm.ru
 * @see      http://retailcrm.ru/docs
 */

namespace Intaro\RetailCrm\Model\Bitrix;

use Intaro\RetailCrm\Component\Json\Mapping;
use Intaro\RetailCrm\Component\Json\Serializer;
use Bitrix\Main\ORM\Data\Result;
use Bitrix\Main\Error;
use Intaro\RetailCrm\Component\Json\Strategy\AnnotationReaderTrait;

/**
 * Class AbstractSerializableModel
 * Clones some functionality of ORM models in order to provide close to ORM interface for those models
 * which doesn't have proper ORM support yet.
 *
 * @package Intaro\RetailCrm\Model\Bitrix
 */
abstract class AbstractSerializableModel
{
    use AnnotationReaderTrait;

    /**
     * Holds data about original fields
     *
     * @var array
     */
    private $originalFields = [];

    /**
     * True if $originalFields is initialized, false otherwise
     *
     * @var bool
     */
    private $originalPropertiesWritten = false;

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
     * Should return filled entity by provided primary key
     *
     * @param mixed $primary
     *
     * @return mixed
     */
    abstract public static function getEntityByPrimary($primary);

    /**
     * AbstractSerializableModel constructor.
     * Will fill model with data if primary key is passed.
     * Better use repository getById method. It is faster, and passing primary by constructor uses it's under the hood.
     *
     * @param mixed $primary
     *
     * @throws \ReflectionException
     */
    public function __construct($primary = null)
    {
        if ($primary !== null) {
            $thisClassName = get_class($this);
            $instance = static::getEntityByPrimary($primary);

            if ($instance instanceof $thisClassName) {
                $instanceReflection = new \ReflectionClass($instance);

                foreach ($instanceReflection->getProperties() as $property) {
                    $thisProperty = new \ReflectionProperty($thisClassName, $property->getName());

                    $property->setAccessible(true);
                    $thisProperty->setAccessible(true);
                    $thisProperty->setValue($this, $property->getValue($instance));
                }
            }

            $this->postDeserialize();
        }
    }

    /**
     * Tries to add object via base class
     *
     * @return \Bitrix\Main\ORM\Data\Result
     * @throws \ReflectionException
     */
    public function add(): Result
    {
        $result = null;
        $instance = null;
        $data = $this->clearUnchangedOrEmptyFields($this->serialize());
        $baseClass = $this->getBaseClass();

        if (empty($data)) {
            return new Result();
        }

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
     * @throws \ReflectionException
     */
    public function save(): Result
    {
        $primary = $this->getPrimaryKeyData();

        if (empty($primary)) {
            return $this->add();
        }

        $result = null;
        $instance = null;
        $data = $this->clearUnchangedOrEmptyFields($this->serialize());
        $baseClass = $this->getBaseClass();

        if (empty($data)) {
            return new Result();
        }

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
     * This will record all original properties
     *
     * @Mapping\PostDeserialize()
     */
    public function postDeserialize(): void
    {
        if (!$this->originalPropertiesWritten) {
            $thisReflection = new \ReflectionClass($this);

            foreach ($thisReflection->getProperties() as $property) {
                $name = static::annotationReader()->getPropertyAnnotation(
                    $property,
                    Mapping\SerializedName::class
                );

                if (!($name instanceof Mapping\SerializedName)) {
                    continue;
                }

                $property->setAccessible(true);
                $this->originalFields[$property->getName()] = crc32(serialize($property->getValue($this)));
            }

            $this->originalPropertiesWritten = true;
        }
    }

    /**
     * This will remove null fields from serialized array if they wasn't set that intentionally, to erase field data.
     * In other words, empty and unchanged fields will be removed, but fields which are empty now, but has been changed,
     * will stay in the result array.
     *
     * @param array $fields
     *
     * @throws \ReflectionException
     *
     * @return array
     */
    private function clearUnchangedOrEmptyFields(array $fields): array
    {
        $reflection = new \ReflectionClass($this);

        foreach ($reflection->getProperties() as $property) {
            $name = static::annotationReader()->getPropertyAnnotation(
                $property,
                Mapping\SerializedName::class
            );

            if (!($name instanceof Mapping\SerializedName)) {
                continue;
            }

            $property->setAccessible(true);
            $value = $property->getValue($this);

            if ((empty($value) && $this->originalFields[$property->getName()] === crc32(serialize($value)))
                || $this->originalFields[$property->getName()] === crc32(serialize($value))
            ) {
                unset($fields[$name->name]);
            }
        }

        return $fields;
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
