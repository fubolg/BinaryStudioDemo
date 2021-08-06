<?php declare(strict_types=1);

namespace BinaryStudioDemo\MappingBundle\Event;

use Symfony\Contracts\EventDispatcher\Event;

/**
 * Class MappingRequestSuccessEvent
 * @package BinaryStudioDemo\MappingBundle\Event
 */
class MappingRequestSuccessEvent extends Event
{
    const NAME = 'mapping.request.success';

    /**
     * @var array
     */
    private $map;

    /**
     * @var int
     */
    private $channelId;

    /**
     * MappingRequestSuccessEvent constructor.
     *
     * @param array $map
     * @param int   $channelId
     */
    public function __construct(array $map, int $channelId)
    {
        $this->map = $map;
        $this->channelId = $channelId;
    }

    /**
     * @return mixed
     */
    public function getMap(): array
    {
        return $this->map;
    }

    /**
     * @return int
     */
    public function getChannelId(): int
    {
        return $this->channelId;
    }
}