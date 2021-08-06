<?php declare(strict_types=1);

namespace BinaryStudioDemo\MappingBundle\Model\Accessor;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Trait Type
 * @package BinaryStudioDemo\MappingBundle\Model\Accessor
 */
trait Type
{
    /**
     * @ORM\Column(type="string", length=20)
     * @Assert\NotBlank()
     */
    protected $type;

    /**
     * @return string
     */
    public function getType(): string
    {
        return $this->type;
    }

    /**
     * @param string $type
     */
    public function setType(string $type): void
    {
        $this->type = $type;
    }
}