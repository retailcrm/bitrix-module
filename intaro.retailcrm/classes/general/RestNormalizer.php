<?php

 /**
 * RestNormalizer - The main class
 *
 * @author    Dmitry Mamontov <d.slonyara@gmail.com>
 * @copyright 2015 Dmitry Mamontov <d.slonyara@gmail.com>
 * @license   http://www.opensource.org/licenses/BSD-3-Clause  The BSD 3-Clause License
 * @version   Release: 1.0.0
 * @link      https://github.com/dmamontov/restnormalizer/
 * @since     Class available since Release 1.0.0
 */

class RestNormalizer
{
    public $clear = true;
    private $validation = array();
    private $originalValidation = array();    
    private $server;

    /**
     * Class constructor
     * @return void
     * @access public
     * @final
     */
    final public function __construct()
    {
        if (function_exists('date_default_timezone_set') && function_exists('date_default_timezone_get')) {
            date_default_timezone_set(@date_default_timezone_get());
        }
        $this->server = \Bitrix\Main\Context::getCurrent()->getServer()->getDocumentRoot();
    }

    /**
     * Parsing the file validation
     * @param string $file The path to the file validation
     * @return boolean
     * @access private
     * @final
     */
    final private function parseConfig($file)
    {
        if (json_decode(file_get_contents($file)) !== null) {
            $this->originalValidation = json_decode(file_get_contents($file), true);
            return true;
        } else {
            return false;
        }
    }

    /**
     * Starting the process of normalization of the data
     * @param array $data The key is to sort the data validation
     * @param string $key Data normalization
     * @return array
     * @access public
     * @final
     */
    final public function normalize($data, $key = false, $file = '/bitrix/modules/intaro.retailcrm/classes/general/config/retailcrm.json')
    {
        $server = \Bitrix\Main\Context::getCurrent()->getServer()->getDocumentRoot();
        $file = $server . $file;
        if (is_null($file) || is_file($file) === false
            || json_decode(file_get_contents($file)) === null
            || $this->parseConfig($file) === false) {
                RCrmActions::eventLog('RestNormalizer', 'intaro.retailcrm', 'Incorrect file normalize.');
                return false;
        }
        
        if (is_string($data)) {
            $data = json_decode($data, true);
        }

        if (is_string($key) && isset($this->originalValidation[ $key ])) {
            $this->validation = $this->originalValidation[ $key ];
        } else {
            $this->validation = $this->originalValidation;
        }

        if (!is_array($data) || count($data) < 1) {
            RCrmActions::eventLog('RestNormalizer', 'intaro.retailcrm', 'Incorrect data array.');
            return false;
        }

        return $this->formatting($data);
    }

    /**
     * Data formatting
     * @param array $data The key is to sort the data validation
     * @param boolean $skip Skip perform methods intended for the first run
     * @return array
     * @access private
     * @final
     */
    final private function formatting($data, $skip = false)
    {
        $formatted = array();

        foreach ($data as $code => $value) {
            if (isset($this->validation[ $code ]) && $this->validation[ $code ]['type'] == 'skip') {
                $formatted[ $code ] = $value;
            } elseif (isset($this->validation[ $code ]) && is_array($value) === false) {
                $formatted[ $code ] = $this->setFormat($value, $this->validation[ $code ]);
            } elseif (is_array($value)) {
                $formatted[ $code ] = $this->formatting($value, true);
            }

            if ($formatted[ $code ] === null || $formatted[ $code ] === '' || count($formatted[ $code ]) < 1) {
                if ($this->clear === true) {
                    unset($formatted[ $code ]);
                }

                if (isset($this->validation[ $code ]['required']) && $this->validation[ $code ]['required'] === true) {
                    $formatted = array();
                    break;
                }
            }

        }

        if ($skip === false) {
            foreach ($this->validation as $code => $valid) {
                if (isset($valid['required']) && $valid['required'] === true && isset($formatted[ $code ]) === false) {
                    RCrmActions::eventLog('RestNormalizer', 'intaro.retailcrm', "NOT VALID: $code");
                }
            }

            $formatted = $this->multiConvert($formatted);
        }

        return count($formatted) < 1 ? false : $formatted;
    }

    /**
     * Formatting data depending on the type
     * @param mixed $data The value to be formatted
     * @param array $validation The data for the current data type validation
     * @return mixed
     * @access private
     * @final
     */
    final private function setFormat($data, $validation)
    {
        $format = null;

        switch ($validation['type']) {
            case 'string':
                $format = $this->setString($data, $validation);
                break;
            case 'int':
                $format = $this->setInt($data, $validation);
                break;
            case 'double':
                $format = $this->setDouble($data, $validation);
                break;
            case 'bool':
                $format = $this->setBool($data, $validation);
                break;
            case 'datetime':
                $format = $this->setDateTime($data, $validation);
                break;
            case 'enum':
                $format = $this->setEnum($data, $validation);
                break;
        }

        return $format;
    }

    /**
     * Formatting data for strings
     * @param string $data String to formatting
     * @param array $validation The data for the current data type validation
     * @return string
     * @access private
     * @final
     */
    final private function setString($data, $validation)
    {
        $data = trim((string) $data);

        if (isset($validation['default']) && is_string($validation['default']) && trim($validation['default']) != ''
            && ($data == '' || is_string($data) === false)) {
            $data = trim($validation['default']);
        } elseif ($data == '' || is_string($data) === false) {
            return null;
        } elseif (isset($validation['min']) && mb_strlen($data) < $validation['min']) {
            $pad = isset($validation['pad']) && mb_strlen($validation['pad']) == 1 ? $validation['pad'] : ' ';
            $data .= str_repeat($pad, $validation['min'] - mb_strlen($data));
        } elseif (isset($validation['max']) && mb_strlen($data) > $validation['max']) {
            $data = mb_substr($data, 0, $validation['max']);
        }

        return (string) $data;
    }

    /**
     * Formatting data for integers
     * @param integer $data Integer to formatting
     * @param array $validation The data for the current data type validation
     * @return integer
     * @access private
     * @final
     */
    final private function setInt($data, $validation)
    {
        if (isset($validation['default']) && is_numeric($validation['default']) && is_numeric($data) === false) {
            $data = $validation['default'];
        } elseif (is_numeric($data) === false) {
            return null;
        } elseif (isset($validation['min']) && $data < $validation['min']) {
            $data += $validation['min'] - $data;
        } elseif (isset($validation['max']) && $data > $validation['max']) {
            $data -= $data - $validation['max'];
        }

        return (int) $data;
    }

    /**
     * Formatting data for floating-point numbers
     * @param float $data Floating-point number to formatting
     * @param array $validation The data for the current data type validation
     * @return float
     * @access private
     * @final
     */
    final private function setDouble($data, $validation)
    {
        if (isset($validation['default']) && is_numeric($validation['default']) && is_numeric($data) === false) {
            $data = $validation['default'];
        } elseif (is_numeric($data) === false) {
            return null;
        } elseif (isset($validation['min']) && $data < $validation['min']) {
            $data += $validation['min'] - $data;
        } elseif (isset($validation['max']) && $data > $validation['max']) {
            $data -= $data - $validation['max'];
        }

        if (isset($validation['decimals'])) {
            $data = number_format($data, $validation['decimals'], '.', '');
        }

        return (double) $data;
    }

    /**
     * Formatting data for logical values
     * @param boolean $data Boolean value to formatting
     * @param array $validation The data for the current data type validation
     * @return boolean
     * @access private
     * @final
     */
    final private function setBool($data, $validation)
    {
        if (isset($validation['default']) && is_bool($validation['default']) && is_bool($data) === false) {
            $data = $validation['default'];
        } elseif (is_bool($data) === false) {
            return null;
        }

        return (bool) $data;
    }

    /**
     * Formatting data for date and time
     * @param mixed $data Date and time of to formatting
     * @param array $validation The data for the current data type validation
     * @param boolean $skip Skip perform methods intended for the first run
     * @return mixed
     * @access private
     * @final
     */
    final private function setDateTime($data, $validation, $skip = false)
    {
        if (is_a($data, 'DateTime') && isset($validation['format'])) {
            $data = (string) $data->format($validation['format']);
        } elseif (is_string($data) && isset($validation['format']) && strtotime($data) !== false) {
            $data = (string) date($validation['format'], strtotime($data));
        } elseif (is_numeric($data) && isset($validation['format'])) {
            $data = (string) date($validation['format'], (int) $data);
        } elseif (is_numeric($data)) {
            $data = (int) $data;
        } elseif (isset($validation['format'])) {
            $data = (string) date($validation['format']);
        } elseif (isset($validation['default']) && $skip === false) {
            $data = $this->setDateTime(time(), $validation, true);
        } else {
            return null;
        }

        return $data;
    }

    /**
     * Formatting data for enum
     * @param string $data Enum to formatting
     * @param array $validation The data for the current data type validation
     * @return string
     * @access private
     * @final
     */
    final private function setEnum($data, $validation)
    {
        if (isset($validation['values']) === false || count($validation['values']) < 1) {
            return null;
        } elseif (isset($validation['default']) && in_array($validation['default'], $validation['values']) === false) {
            return null;
        } elseif (in_array($data, $validation['values']) === false
                  && isset($validation['default']) && in_array($validation['default'], $validation['values'])) {
            $data = $validation['default'];
        } elseif (in_array($data, $validation['values']) === false) {
            return null;
        }

        return $data;
    }

    /**
     * Installing the specified encoding
     * @param array $data The original dataset
     * @return array
     * @access private
     * @final
     */
    final private function multiConvert($data)
    {
        global $APPLICATION;

        if (is_array($data)) {
            foreach ($data as $code => $value) {
                $data[$APPLICATION->ConvertCharset($code, SITE_CHARSET, 'utf-8')] = is_array($value)
                                                                        ? $this->multiConvert($value)
                                                                        : $APPLICATION->ConvertCharset($value, SITE_CHARSET, 'utf-8');
            }
            return $data;
        } else {
            return $APPLICATION->ConvertCharset($data, SITE_CHARSET, 'utf-8');
        }

        return $data;
    }
}
?>