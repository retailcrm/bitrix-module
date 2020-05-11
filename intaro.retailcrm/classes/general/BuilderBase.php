<?php

/**
 * Class BuilderBase
 */
class BuilderBase
{
    /**
     * @param array $array
     * @param array $symbols
     * @return array
     */
    function arrayClear(array $array, array $symbols = array('', 0, null))
    {
        return array_diff($array, $symbols);
    }

    /**
     * @param $data
     * @return array
     */
    function objectToArray($data)
    {
        return $this->arrayClear(json_decode(json_encode($data), true));
    }
}
