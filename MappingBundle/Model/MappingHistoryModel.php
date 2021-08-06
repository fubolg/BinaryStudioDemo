<?php declare(strict_types=1);

namespace BinaryStudioDemo\MappingBundle\Model;

use BinaryStudioDemo\CoreBundle\Mixins\ResourceTrait;
use BinaryStudioDemo\MappingBundle\Interfaces\MappingHistoryInterface;

use Doctrine\ORM\Mapping as ORM;
use BinaryStudioDemo\MappingBundle\Model\Accessor;

/**
 * @deprecated
 * Class MappingHistoryModel
 * @package BinaryStudioDemo\MappingBundle\Model
 */
abstract class MappingHistoryModel implements MappingHistoryInterface
{
    use ResourceTrait,
        Accessor\UniqueKey,
        Accessor\Channel,
        Accessor\OriginalName,
        Accessor\OriginalValue,
        Accessor\CommonIdentifier,
        Accessor\Type;
}