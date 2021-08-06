<?php declare(strict_types=1);

namespace BinaryStudioDemo\MappingBundle\Model\Accessor;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Trait Channel
 * @package BinaryStudioDemo\MappingBundle\Model\Accessor
 */
trait Channel
{
    /**
     * @ORM\Column(type="integer")
     * @Assert\NotBlank()
     */
    protected $channel;

    /**
     * @return mixed
     */
    public function getChannel(): int
    {
        return $this->channel;
    }

    /**
     * @param mixed $channel
     */
    public function setChannel($channel): void
    {
        $this->channel = $channel;
    }
}