<?php declare(strict_types=1);

namespace BinaryStudioDemo\MappingBundle\Mapper;

use BinaryStudioDemo\CoreBundle\Interfaces\ContextInterface;
use BinaryStudioDemo\IntegrationBundle\Traits\ErrorMessageAwaredTrait;
use BinaryStudioDemo\MappingBundle\Interfaces\MapperInterface;

/**
 * Class ChainMapper
 * @package BinaryStudioDemo\MappingBundle\Mapper
 */
class ChainMapper implements MapperInterface
{
    use ErrorMessageAwaredTrait;

    private iterable $mappers;
    private ?MapperInterface $currentMapper = null;
    private ?MapperInterface $lastSupportedMapper = null;

    /**
     * ChainMapper constructor.
     *
     * @param iterable $mappers
     */
    public function __construct(iterable $mappers)
    {
        $this->mappers = $mappers;
    }

    /**
     * @return string|null
     */
    public function getErrorMessage(): ?string
    {
        if(null !== $this->currentMapper) {
            return $this->currentMapper->getErrorMessage();
        }

        return null;
    }

    /**
     * @param array            $subject
     * @param ContextInterface $context
     *
     * @return bool
     */
    public function supports(array $subject, ContextInterface $context): bool
    {
        # prevent redundant iterations
        if (null !== $this->lastSupportedMapper && $this->lastSupportedMapper->supports($subject, $context)) {
            return true;
        }

        /** @var MapperInterface $mapper */
        foreach ($this->mappers as $mapper) {
            if ($mapper->supports($subject, $context)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param array $subject
     * @param ContextInterface $context
     * @return array|null
     * @throws \Exception
     */
    public function run(array $subject, ContextInterface $context): ?array
    {
        if (empty($this->mappers)) {
            throw new \Exception('No registered mappers found');
        }

        # prevent redundant iterations
        if (null !== $this->lastSupportedMapper && $this->lastSupportedMapper->supports($subject, $context)) {
            return $this->lastSupportedMapper->run($subject, $context);
        }

        $this->currentMapper = null;
        /** @var MapperInterface $mapper */
        foreach ($this->mappers as $mapper) {
            if ($mapper->supports($subject, $context)) {
                $this->currentMapper = $mapper;
                $this->lastSupportedMapper = $mapper;
                return $mapper->run($subject, $context);
            }
        }

        return $subject;
    }
}