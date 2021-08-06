<?php declare(strict_types=1);

namespace BinaryStudioDemo\MappingBundle\Interfaces;

/**
 * @deprecated
 * Interface MappingHistoryInterface
 * @package BinaryStudioDemo\MappingBundle\Interfaces
 */
interface MappingHistoryInterface
{
    /**
     * @return int
     */
    public function getChannel(): int;

    /**
     * @param int $channelId
     */
    public function setChannel(int $channelId): void;

    /**
     * @return string
     */
    public function getType(): string;

    /**
     * @param string $string
     */
    public function setType(string $string): void;

    /**
     * @return mixed
     */
    public function getCommonIdentifier(): string;

    /**
     * @param $commonIdentifier
     */
    public function setCommonIdentifier(string $commonIdentifier): void;

    /**
     * @return string
     */
    public function getOriginalName(): string;

    /**
     * @param string $originalName
     */
    public function setOriginalName(string $originalName): void;

    /**
     * @return null|string
     */
    public function getOriginalValue(): ?string;

    /**
     * @param null|string $originalValue
     */
    public function setOriginalValue(?string $originalValue): void;
}