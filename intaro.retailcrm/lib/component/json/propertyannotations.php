<?php

/**
 * @category Integration
 * @package  Intaro\RetailCrm\Component\Json
 * @author   RetailCRM <integration@retailcrm.ru>
 * @license  http://retailcrm.ru Proprietary
 * @link     http://retailcrm.ru
 * @see      http://help.retailcrm.ru
 */

namespace Intaro\RetailCrm\Component\Json;

use Intaro\RetailCrm\Component\Json\Mapping\Accessor;
use Intaro\RetailCrm\Component\Json\Mapping\BitrixBoolean;
use Intaro\RetailCrm\Component\Json\Mapping\SerializedName;
use Intaro\RetailCrm\Component\Json\Mapping\Type;

/**
 * Class PropertyAnnotations
 *
 * @category PropertyAnnotations
 * @package  Intaro\RetailCrm\Component\Json
 * @author   RetailDriver LLC <integration@retailcrm.ru>
 * @license  https://retailcrm.ru Proprietary
 * @link     http://retailcrm.ru
 * @see      https://help.retailcrm.ru
 */
class PropertyAnnotations
{
    /**
     * @var SerializedName
     */
    public $serializedName;

    /**
     * @var Accessor
     */
    public $accessor;

    /**
     * @var Type
     */
    public $type;

    /**
     * @var BitrixBoolean
     */
    public $bitrixBoolean;

    /**
     * PropertyAnnotations constructor.
     *
     * @param array $annotations
     */
    public function __construct(array $annotations = [])
    {
        foreach ($annotations as $annotation) {
            switch (get_class($annotation)) {
                case Type::class:
                    $this->type = $annotation;
                    break;
                case SerializedName::class:
                    $this->serializedName = $annotation;
                    break;
                case Accessor::class:
                    $this->accessor = $annotation;
                    break;
                case BitrixBoolean::class:
                    $this->bitrixBoolean = $annotation;
                    break;
            }
        }
    }
}
