<?php

/**
 * Class AbstractBuilder
 */
abstract class AbstractBuilder
{
    /**
     * @param string $key
     * @param mixed $default
     * @return mixed|null
     */
    public function getValue($key, $default = NULL)
    {
        return isset($this->dataCrm[$key]) && !empty($this->dataCrm[$key]) ?  $this->dataCrm[$key] : $default;
    }

    /**
     * @param array $array
     * @param string $key
     * @param mixed $default
     * @return mixed|null
     */
    public function getValueArray($array, $key, $default = NULL)
    {
        return isset($this->dataCrm[$array][$key]) && !empty($this->dataCrm[$array][$key]) ?  $this->dataCrm[$array][$key] : $default;
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
     * @param $data
     * @return array
     */
    public function objectToArray($data)
    {
        return $this->arrayClear(json_decode(json_encode($data), true));
    }

    /**
     *
     * @param string|array|\SplFixedArray $str in utf-8
     *
     * @return array|bool|\SplFixedArray|string $str in SITE_CHARSET
     * @global                            $APPLICATION
     */
    public function fromJSON($str)
    {
        global $APPLICATION;

        return $APPLICATION->ConvertCharset($str, 'utf-8', SITE_CHARSET);
    }
}
