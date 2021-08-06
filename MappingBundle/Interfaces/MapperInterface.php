<?php declare(strict_types=1);

namespace BinaryStudioDemo\MappingBundle\Interfaces;

use BinaryStudioDemo\CoreBundle\Interfaces\ContextInterface;
use BinaryStudioDemo\ImportExportBundle\Interfaces\ErrorMessageAwaredInterface;

/**
 * Interface MapperInterface
 * @package BinaryStudioDemo\MappingBundle\Interfaces
 */
interface MapperInterface extends ErrorMessageAwaredInterface {

    /**
     * @param array                         $subject
     * @param ContextInterface $context
     *
     * @return bool
     */
    public function supports(array $subject, ContextInterface $context): bool;

    /**
     * @param array                         $product
     * @param ContextInterface $context
     *
     * @return array|null
     */
    public function run(array $product, ContextInterface $context): ?array;
}