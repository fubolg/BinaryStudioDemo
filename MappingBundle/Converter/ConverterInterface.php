<?php declare(strict_types=1);

namespace BinaryStudioDemo\MappingBundle\Converter;

/**
 * Interface ConverterInterface
 * @package BinaryStudioDemo\MappingBundle\Converter
 */
interface ConverterInterface
{
    /**
     * @param int $channelId
     *
     * @return ConverterInterface
     */
    public function setChannelId(int $channelId): ConverterInterface;

    /**
     * @param mixed $subject
     *
     * @return array|null
     */
    public function convert($subject): ?array;
}
