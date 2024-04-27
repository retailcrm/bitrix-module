<?php

/**
 * @category Integration
 * @package  Intaro\RetailCrm\Model\Api
 * @author   RetailCRM <integration@retailcrm.ru>
 * @license  MIT
 * @link     http://retailcrm.ru
 * @see      http://retailcrm.ru/docs
 */

namespace Intaro\RetailCrm\Model\Api;

use Intaro\RetailCrm\Component\Json\Mapping\PostDeserialize;
use Intaro\RetailCrm\Component\Json\Mapping\PostSerialize;
use Intaro\RetailCrm\Component\ServiceLocator;
use Intaro\RetailCrm\Service\Utils;

/**
 * Class AbstractApiModel
 *
 * @package Intaro\RetailCrm\Model\Api
 */
class AbstractApiModel implements ApiModelInterface
{
    /** @var Utils */
    private $utils;

    /**
     * AbstractApiModel constructor.
     */
    public function __construct()
    {
        $this->utils = ServiceLocator::get(Utils::class);
    }

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
        return $this->utils->clearArray($fields);
    }
}
