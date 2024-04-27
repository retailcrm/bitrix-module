<?php

/**
 * @category RetailCRM
 * @package  RetailCRM\Model
 * @author   RetailCRM <integration@retailcrm.ru>
 * @license  MIT
 * @link     http://retailcrm.ru
 * @see      http://retailcrm.ru/docs
 */

/**
 * Class BaseModel
 *
 * @category RetailCRM
 * @package RetailCRM\Model
 */
abstract class BaseModel
{
    /**
     * @return array
     */
    public function getObjectToArray()
    {
        return $this->arrayClear(call_user_func('get_object_vars', $this));
    }

    /**
     * @param array $array
     * @param array $symbols
     * @return array
     */
    public function arrayClear(array $array, array $symbols = array('', 0, null))
    {
        return array_diff($array, $symbols);
    }

    /**
     * @param $array
     * @return $this
     */
    public function getArrayToObject($array)
    {
        foreach ($array as $key => $value) {
            $this->$key = $value;
        }

        return $this;
    }
}
