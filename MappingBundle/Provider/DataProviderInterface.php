<?php declare(strict_types=1);

namespace BinaryStudioDemo\MappingBundle\Provider;

/**
 * Interface DataProviderInterface
 * @package BinaryStudioDemo\MappingBundle\Interfaces
 */
interface DataProviderInterface
{
    const MS_MAPPING_NAME = 'ms_mapping';
    const DEFAULT_QUEUE_SYNC = 'mapping_replacements_sync';

    /**
     * @param int $channelId
     *
     * @return array
     */
    public function getReplacementsMap(int $channelId): ?array;

    /**
     * @param array $filter
     *
     * @return array|null
     */
    public function unlinkReplacements(array $filter): ?array;

    /**
     * @param array $filter
     *
     * @return array|null
     */
    public function getReplacements(array $filter = []): ?array;

    /**
     * @param array $data
     * @param int   $channelId
     *
     * @return array|null
     */
    public function syncReplacements(array $data, int $channelId): ?array;

    /**
     * @param string $queue
     * @param array  $data
     *
     * @return mixed
     */
    public function send(string $queue, array $data);
}
