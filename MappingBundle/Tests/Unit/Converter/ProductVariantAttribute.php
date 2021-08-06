<?php declare(strict_types=1);

namespace BinaryStudioDemo\MappingBundle\Tests\Unit\Converter;

use BinaryStudioDemo\CoreBundle\Interfaces\ResourceInterface;
use BinaryStudioDemo\CoreBundle\Mixins\ResourceTrait;

/**
 * Class ProductVariantAttribute
 * @package BinaryStudioDemo\MappingBundle\Tests\Unit\Converter
 */
class ProductVariantAttribute implements ResourceInterface
{
    use ResourceTrait;
}