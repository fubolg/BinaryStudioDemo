<?php declare(strict_types=1);

namespace BinaryStudioDemo\MappingBundle\Model\Accessor;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Trait CommonIdentifier
 * @package BinaryStudioDemo\MappingBundle\Model\Accessor
 */
trait CommonIdentifier
{
    /**
     * @ORM\Column(name="common_identifier", type="string", length=1000)
     */
    protected $commonIdentifier;

    /**
     * @return mixed
     */
    public function getCommonIdentifier(): string
    {
        return $this->commonIdentifier;
    }

    /**
     * @param $commonIdentifier
     */
    public function setCommonIdentifier(string $commonIdentifier): void
    {
        $this->commonIdentifier = $commonIdentifier;
    }
}