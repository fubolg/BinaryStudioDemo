<?php declare(strict_types=1);

namespace BinaryStudioDemo\MappingBundle\Event;

use BinaryStudioDemo\CoreBundle\Interfaces\ContextInterface;
use Symfony\Contracts\EventDispatcher\Event;

/**
 * Class MappingFailedEvent
 * @package BinaryStudioDemo\MappingBundle\Event
 */
class MappingFailedEvent extends Event
{
    /**
     * @var array
     */
    private array $item;

    /**
     * @var string
     */
    private string $errorMessage;

    /**
     * @var ContextInterface
     */
    private ContextInterface $context;

    /**
     * TransformationFailedEvent constructor.
     *
     * @param array            $item
     * @param string           $errorMessage
     * @param ContextInterface $context
     */
    public function __construct(array $item, string $errorMessage, ContextInterface $context)
    {
        $this->item = $item;
        $this->errorMessage = $errorMessage;
        $this->context = $context;
    }

    /**
     * @return array
     */
    public function getItem(): array
    {
        return $this->item;
    }

    /**
     * @return string
     */
    public function getErrorMessage(): string
    {
        return $this->errorMessage;
    }

    /**
     * @return ContextInterface
     */
    public function getContext(): ContextInterface
    {
        return $this->context;
    }
}