<?php declare(strict_types=1);

namespace BinaryStudioDemo\MappingBundle\Mapper;

use BinaryStudioDemo\IntegrationBundle\Traits\ErrorMessageAwaredTrait;
use BinaryStudioDemo\MappingBundle\Converter\ConverterInterface;
use BinaryStudioDemo\MappingBundle\Interfaces\MapperInterface;
use BinaryStudioDemo\MappingBundle\Provider\MappingDataProvider;
use BinaryStudioDemo\PlatformBundle\Mapping\Automapping\AutomappingBuilderInterface;

/**
 * Class AbstractMapper
 * @package BinaryStudioDemo\MappingBundle\Mapper
 */
abstract class AbstractMapper implements MapperInterface
{
    use ErrorMessageAwaredTrait;

    protected ?int $channelId = null;
    protected array $convertedCache = [];
    protected ConverterInterface $converter;
    protected AutomappingBuilderInterface $automappingBuilder;
    protected MappingDataProvider $mappingDataProvider;

    /**
     * ProductImportMapper constructor.
     *
     * @param ConverterInterface          $converter
     * @param AutomappingBuilderInterface $automappingBuilder
     * @param MappingDataProvider         $mappingDataProvider
     */
    public function __construct(
        ConverterInterface $converter,
        AutomappingBuilderInterface $automappingBuilder,
        MappingDataProvider $mappingDataProvider
    )
    {
        $this->converter = $converter;
        $this->automappingBuilder = $automappingBuilder;
        $this->mappingDataProvider = $mappingDataProvider;
    }

    /**
     * @param string $type
     * @param        $attributeName
     * @param array  $extra
     */
    final protected function automap(string $type, $attributeName, array $extra = []): void
    {
        $cached = $this->convertedCache[$type][$attributeName] ?? null;
        if (null === $cached && !empty($attributeName)) {

            $data = [
                'label' => (string)$attributeName,
                'name' => (string)$attributeName,
                'id' => is_int($attributeName) ? (int)$attributeName : null
            ];

            if(array_key_exists('is_configurable', $extra)) {
                $data['is_configurable'] = (bool) $extra['is_configurable'];
            }

            if(empty($data['id']) && empty($data['name'])) {
                return;
            }

            $automap = $this->automappingBuilder->build([
                $type => [
                    $data
                ]
            ]);

            if (!empty($automap)) {
                # update automapped in conversion map
                foreach ($automap as $type => $elements) {
                    foreach ($elements as $elemData) {
                        $refTo = (int) ($elemData['referenceTo'] ?? 0);
                        if($refTo > 0) {
                            $this->convertedCache[$type][$attributeName] = ['id' => $refTo];
                        }
                    }
                }

                # send updates to Microservice
                $this->mappingDataProvider->syncReplacements($automap, $this->channelId);
            }
        }
    }
}