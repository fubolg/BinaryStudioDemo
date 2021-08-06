<?php declare(strict_types=1);

namespace BinaryStudioDemo\MappingBundle\Converter;

use BinaryStudioDemo\CoreBundle\Interfaces\LoggerAwareInterface;
use BinaryStudioDemo\CoreBundle\Traits\LoggerAwareTrait;
use BinaryStudioDemo\MappingBundle\Factory\SchemaFactoryInterface;
use BinaryStudioDemo\MappingBundle\Factory\Schema;

/**
 * Class Converter
 * @package BinaryStudioDemo\MappingBundle\Converter
 */
final class Converter implements ConverterInterface, LoggerAwareInterface
{
    use LoggerAwareTrait;

    private ?int $channelId = null;
    private SchemaFactoryInterface $schemaFactory;

    /**
     * Converter constructor.
     *
     * @param SchemaFactoryInterface   $schemaFactory
     */
    public function __construct(SchemaFactoryInterface $schemaFactory)
    {
        $this->schemaFactory = $schemaFactory;
    }

    /**
     * {@inheritdoc}
     */
    public function setChannelId(int $channelId): ConverterInterface
    {
        $this->channelId = $channelId;
        return $this;
    }

    /**
     * @param mixed $subject
     * @return array|null
     */
    public function convert($subject): ?array
    {
        if ($subject === null) {
            return null;
        }

        if (!$this->channelId) {
            throw new \LogicException('ChannelId must be set before conversion');
        }

        $type = $subject['type'] ?? null;

        if (null === $type) {
            throw new \InvalidArgumentException('Conversion subject must contain "type" attribute');
        }

        if(is_array($subject)) {
            $subject['channel'] = $this->channelId;
            $subject['type'] = $type;
        }

        $key = null;
        $keyId = null;
        $keyName = null;

        if(is_array($subject) && array_key_exists('id', $subject)) {
            $key = $keyId = sprintf('%s.%s.%s.e', $this->channelId, $type, $subject['id']);
        }

        if(is_array($subject) && array_key_exists('data', $subject)) {
            if(array_key_exists('id', $subject['data']) && null !== $subject['data']['id']) {
                $key = $keyId = sprintf('%s.%s.%s.i', $this->channelId, $type, $subject['data']['id']);
            }

            if(array_key_exists('name', $subject['data']) && null !== $subject['data']['name']) {
                $key = $keyName = sprintf('%s.%s.%s.i', $this->channelId, $type, strtolower((string) $subject['data']['name']));
            }

//            if($type === MappingTypes::TYPE_RELATIONSHIP && array_key_exists('attributes', $subject['data']) && null !== $subject['data']['attributes']) {
//                $attributes = $subject['data']['attributes'];
//                asort($attributes);
//                $key = $keyName = sprintf('%s.%s.%s.e', $this->channelId, $type, strtolower(implode('-', $attributes)));
//            }
        }

        if(null !== $key) {
            /** @var Schema $schema */
            $schema = $this->schemaFactory
                ->setChannelId((int) $this->channelId)
                ->createNew();

            $result = $schema->find($key);

            if(null === $result && null !== $keyId) {
                $result = $schema->find($keyId);
            }

            return $result;
        }

        return null;
    }
}
