<?php declare(strict_types=1);

namespace BinaryStudioDemo\MappingBundle\Model\Accessor;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Trait Channel
 * @package BinaryStudioDemo\MappingBundle\Model\Accessor
 */
trait OriginalName
{
    /**
     * @ORM\Column(name="original_name", type="string", length=1000)
     * @Assert\NotBlank()
     */
    protected $originalName;

    /**
     * @return string
     */
    public function getOriginalName(): string
    {
        return $this->originalName;
    }

    /**
     * @param string $originalName
     */
    public function setOriginalName(string $originalName): void
    {
        $this->originalName = $originalName;
    }
}