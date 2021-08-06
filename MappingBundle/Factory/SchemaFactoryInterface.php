<?php declare(strict_types=1);

namespace BinaryStudioDemo\MappingBundle\Factory;

use BinaryStudioDemo\CoreBundle\Interfaces\FactoryInterface;

/**
 * Interface SchemaFactoryInterface
 * @package BinaryStudioDemo\MappingBundle\Schema
 */
interface SchemaFactoryInterface extends FactoryInterface
{
    /**
     * @param array $array
     *
     * @return mixed
     */
    public function enhanceWithData(array $array): void;

    /**
     * @param int $channelId
     *
     * @return $this
     */
    public function setChannelId(int $channelId);

    /**
     * @param array $keys
     */
    public function decreaseKeys(array $keys): void;

    /**
     * @param array $keysAndValues
     */
    public function setKeys(array $keysAndValues): void;
}
