<?php declare(strict_types=1);

namespace BinaryStudioDemo\MappingBundle\Model\Accessor;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Trait OriginalValue
 * @package BinaryStudioDemo\MappingBundle\Model\Accessor
 */
trait OriginalValue
{
    /**
     * @ORM\Column(name="original_value", type="text", nullable=true)
     * @Assert\NotBlank()
     */
    protected $originalValue;

    /**
     * @return null|string
     */
    public function getOriginalValue(): ?string
    {
        return $this->originalValue;
    }

    /**
     * @param null|string $originalValue
     */
    public function setOriginalValue(?string $originalValue): void
    {
        $this->originalValue = $originalValue;
    }
}