<?php
/**
 * PHP version 7.1
 *
 * @category Integration
 * @package  Intaro\RetailCrm\Model\Api
 * @author   retailCRM <integration@retailcrm.ru>
 * @license  MIT
 * @link     http://retailcrm.ru
 * @see      http://retailcrm.ru/docs
 */
namespace Intaro\RetailCrm\Model\Api;

use Intaro\RetailCrm\Component\Json\Mapping\PostDeserialize;
use Intaro\RetailCrm\Component\Json\Mapping\PostSerialize;

/**
 * Class AbstractApiModel
 *
 * @package Intaro\RetailCrm\Model\Api
 */
class AbstractApiModel implements ApiModelInterface
{
    /**
     * @PostDeserialize()
     */
    public function postDeserialize(): void
    {
        foreach ($this as $field => $value) {
            if (null === $value) {
                unset($this->{$field});
            }

            if (is_string($value)) {
                $this->{$field} = null !== json_decode($value, true) ?
                    json_decode($value, true, 512, JSON_BIGINT_AS_STRING) :
                    ('null' !== $value ?
                        $value :
                        null
                    );
            }
        }
    }

    /**
     * Removes empty fields from serialized data
     *
     * @PostSerialize()
     * @param array $fields
     *
     * @return array
     */
    public function postSerialize(array $fields): array
    {
        return \RCrmActions::clearArr($fields);
    }
}
