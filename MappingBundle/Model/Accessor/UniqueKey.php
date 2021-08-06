<?php declare(strict_types=1);

namespace BinaryStudioDemo\MappingBundle\Model\Accessor;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Trait Type
 * @package BinaryStudioDemo\MappingBundle\Model\Accessor
 */
trait UniqueKey
{
    /**
     * @ORM\Column(name="unique_key", type="string", length=255, nullable=false, unique=true)
     * @Assert\NotBlank()
     */
    protected $uniqueKey;

    /**
     * @return string
     */
    public function getUniqueKey(): string
    {
        return $this->uniqueKey;
    }
}