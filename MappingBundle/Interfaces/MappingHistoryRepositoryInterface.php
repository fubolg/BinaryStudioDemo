<?php declare(strict_types=1);

namespace BinaryStudioDemo\MappingBundle\Interfaces;

/**
 * @deprecated
 * Interface MappingHistoryRepositoryInterface
 * @package BinaryStudioDemo\MappingBundle\Interfaces
 */
interface MappingHistoryRepositoryInterface
{
    /**
     * @param array $historyValues
     */
    public function bulkInsertOrUpdate(array $historyValues): void;
}