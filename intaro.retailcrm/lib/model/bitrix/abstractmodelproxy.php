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

use Bitrix\Main\Authentication\Context;
use Bitrix\Main\ORM\Entity;
use Bitrix\Main\ORM\Fields\FieldTypeMask;
use Bitrix\Main\ORM\Objectify\Collection;
use Bitrix\Main\ORM\Objectify\EntityObject;
use Bitrix\Main\ORM\Objectify\Values;
use Bitrix\Main\ORM\Data\Result;

/**
 * Class AbstractModelProxy. Wraps Bitrix objects in order to create phpdoc with necessary annotations.
 *
 * @package Intaro\RetailCrm\Model\Bitrix
 *
 * @method array collectValues($valuesType = Values::ALL, $fieldsMask = FieldTypeMask::ALL)
 * @method Result save()
 * @method Result delete()
 * @method mixed fill($fields = FieldTypeMask::ALL)
 * @method Collection|EntityObject|mixed getId()
 * @method mixed get($fieldName)
 * @method mixed remindActual($fieldName)
 * @method mixed require ($fieldName)
 * @method mixed set($fieldName, $value)
 * @method mixed reset($fieldName)
 * @method mixed unset($fieldName)
 * @method mixed has($fieldName)
 * @method mixed isFilled($fieldName)
 * @method mixed isChanged($fieldName)
 * @method mixed addTo($fieldName, $value)
 * @method mixed removeFrom($fieldName, $value)
 * @method mixed removeAll($fieldName)
 * @method void defineAuthContext(Context $authContext)
 * @method Entity sysGetEntity()
 * @method array sysGetPrimary()
 * @method mixed sysGetRuntime($name)
 * @method EntityObject sysSetRuntime($name, $value)
 * @method void sysSetActual($fieldName, $value)
 * @method void sysChangeState($state)
 * @method int sysGetState()
 * @method mixed sysGetValue($fieldName, $require = false)
 * @method EntityObject sysSetValue($fieldName, $value)
 * @method bool sysHasValue($fieldName)
 * @method bool sysIsFilled($fieldName)
 * @method bool sysIsChanged($fieldName)
 * @method bool sysHasPrimary()
 * @method void sysOnPrimarySet()
 * @method void sysAddOnPrimarySetListener($callback)
 * @method EntityObject sysUnset($fieldName)
 * @method EntityObject sysReset($fieldName)
 * @method EntityObject sysResetRelation($fieldName)
 * @method array sysRequirePrimary()
 * @method array sysGetIdleFields($fields = [])
 * @method array sysGetIdleFieldsByMask($mask = FieldTypeMask::ALL)
 * @method Result sysSaveRelations(Result $result)
 * @method void sysPostSave()
 * @method void sysAddToCollection($fieldName, $remoteObject)
 * @method void sysRemoveFromCollection($fieldName, $remoteObject)
 * @method void sysRemoveAllFromCollection($fieldName)
 * @method Collection sysFillRelationCollection($field)
 * @method string sysMethodToFieldCase($methodName)
 * @method string sysFieldToMethodCase($fieldName)
 */
class AbstractModelProxy implements \ArrayAccess
{
    /** @var \Bitrix\Main\ORM\Objectify\EntityObject */
    protected $entity;

    /**
     * AbstractModelProxy constructor.
     *
     * @param \Bitrix\Main\ORM\Objectify\EntityObject $entity
     */
    public function __construct(EntityObject $entity)
    {
        $this->entity = $entity;
    }

    /**
     * @param string $name
     *
     * @return bool
     */
    public function __isset($name)
    {
        return property_exists(get_class($this->entity), $name);
    }

    /**
     * Pass value to entity
     *
     * @param string $name
     * @param mixed $value
     *
     * @throws \Bitrix\Main\SystemException
     */
    public function __set($name, $value)
    {
        if (property_exists(get_class($this->entity), $name)) {
            $this->entity->$name = $value;
        }

        return $this->entity->__set($name, $value);
    }

    /**
     * Extract value from entity
     *
     * @param string $name
     *
     * @return array|\Bitrix\Main\Authentication\Context|\Bitrix\Main\ORM\Entity|int|mixed
     * @throws \Bitrix\Main\ArgumentException
     * @throws \Bitrix\Main\SystemException
     */
    public function __get($name)
    {
        if (property_exists(get_class($this->entity), $name)) {
            return $this->entity->$name;
        }

        return $this->entity->__get($name);
    }

    /**
     * Call method from entity
     *
     * @param string $name
     * @param array $arguments
     *
     * @return \Bitrix\Main\ORM\Objectify\Collection|bool|mixed|null
     * @throws \Bitrix\Main\ArgumentException
     * @throws \Bitrix\Main\SystemException
     */
    public function __call($name, $arguments)
    {
        if (method_exists($this->entity, $name)) {
            return \call_user_func_array([$this->entity, $name], $arguments);
        }

        return $this->entity->__call($name, $arguments);
    }

    /**
     * @param mixed $offset
     *
     * @return bool
     * @throws \Bitrix\Main\ArgumentException
     * @throws \Bitrix\Main\SystemException
     */
    public function offsetExists($offset)
    {
        return $this->entity->offsetExists($offset);
    }

    /**
     * @param mixed $offset
     *
     * @return \Bitrix\Main\ORM\Objectify\Collection|bool|mixed|null
     * @throws \Bitrix\Main\ArgumentException
     * @throws \Bitrix\Main\SystemException
     */
    public function offsetGet($offset)
    {
        return $this->entity->offsetGet($offset);
    }

    /**
     * @param mixed $offset
     * @param mixed $value
     *
     * @throws \Bitrix\Main\ArgumentException
     * @throws \Bitrix\Main\SystemException
     */
    public function offsetSet($offset, $value)
    {
        $this->entity->offsetSet($offset, $value);
    }

    /**
     * @param mixed $offset
     */
    public function offsetUnset($offset)
    {
        $this->entity->offsetUnset($offset);
    }

    /**
     * @return \Bitrix\Main\ORM\Objectify\EntityObject
     */
    public function getEntity(): EntityObject
    {
        return $this->entity;
    }

    /**
     * Call static method from entity
     *
     * @param $name
     * @param $arguments
     *
     * @return mixed
     */
    public static function __callStatic($name, $arguments)
    {
        if (method_exists(EntityObject::class, $name)) {
            return \call_user_func_array([EntityObject::class, $name], $arguments);
        }

        throw new \RuntimeException('Cannot find method "' . $name . '"');
    }
}
