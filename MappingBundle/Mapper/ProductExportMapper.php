<?php declare(strict_types=1);

namespace BinaryStudioDemo\MappingBundle\Mapper;

use BinaryStudioDemo\CoreBundle\Interfaces\ContextInterface;
use BinaryStudioDemo\ImportExportBundle\Interfaces\StepExecutionContextInterface;
use BinaryStudioDemo\IntegrationBundle\Traits\ErrorMessageAwaredTrait;
use BinaryStudioDemo\MappingBundle\Converter\ConverterInterface;
use BinaryStudioDemo\MappingBundle\Event\MappingRequestSuccessEvent;
use BinaryStudioDemo\MappingBundle\Interfaces\MapperInterface;
use BinaryStudioDemo\MappingBundle\MappingTypes;
use BinaryStudioDemo\ProductBundle\Interfaces\ProductInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Class ProductExportMapper
 * @package BinaryStudioDemo\MappingBundle\Mapper
 */
class ProductExportMapper implements MapperInterface, EventSubscriberInterface
{
    use ErrorMessageAwaredTrait;

    protected ConverterInterface $converter;
    protected array $convertedCache = [];
    private array $disallowTypes = [];
    private ?int $channelId = null;

    /**
     * ProductExportMapper constructor.
     *
     * @param ConverterInterface $converter
     */
    public function __construct(ConverterInterface $converter)
    {
        $this->converter = $converter;
    }

    /**
     * @param array $disallowTypes
     */
    public function disallowConversion(array $disallowTypes = []): void
    {
        $this->disallowTypes = $disallowTypes;
    }

    /**
     * @return array
     */
    public static function getSubscribedEvents(): array
    {
        return [
            MappingRequestSuccessEvent::NAME => 'clearCache'
        ];
    }

    /**
     * @param MappingRequestSuccessEvent $event
     */
    final public function clearCache(MappingRequestSuccessEvent $event): void
    {
        if ($event->getChannelId() === $this->channelId) {
            $this->convertedCache = [];
        }
    }

    /**
     * @param array                         $subject
     * @param ContextInterface $context
     *
     * @return bool
     */
    public function supports(array $subject, ContextInterface $context): bool
    {
        $direction = $context->getValue('direction');
        if($direction !== 'export') {
            return false;
        }

        return array_key_exists('sku', $subject) &&
               array_key_exists('type', $subject);
    }

    /**
     * @param array                         $product
     * @param ContextInterface $context
     *
     * @return array|null
     */
    public function run(array $product, ContextInterface $context): ?array
    {
        # initialize
        $this->channelId = (int) $context->getValue(StepExecutionContextInterface::CHANNEL_ID);
        $this->converter->setChannelId($this->channelId);

        # reset error messages
        $this->resetErrorMessage();

        ##### Convert product

        $product = $this->processVariation($product);
        if (null === $product) return [];

        $product = $this->processRelationship($product);
        if (null === $product) return [];

        $product = $this->processClassification($product);
        $product = $this->processAttributes($product);

        if (is_array($product) && array_key_exists('variants', $product)) {
            foreach ($product['variants'] as $key => &$variant) {
                $variant = $this->processClassification($variant);
                $variant = $this->processAttributes($variant);
                $variant = $this->processLocation($variant);
            }
        }

        return $product;
    }

    /**
     * @param array $subject
     *
     * @return array|null
     */
    protected function processLocation(array $subject): ?array
    {
        if(in_array(MappingTypes::TYPE_LOCATION, $this->disallowTypes)) {
            return $subject;
        }

        if (array_key_exists('locationStock', $subject) && is_array($subject['locationStock'])) {
            $subject['onHand'] = 0;
            foreach ($subject['locationStock'] as $key => $location) {

                if (!array_key_exists('location', $location) || empty($location['location'])) {
                    unset($subject['locationStock'][$key]);
                    continue;
                }

//                if(($location['location']['enabled'] ?? true) === false) {
//                    unset($subject['locationStock'][$key]);
//                    continue;
//                }
                $subject['onHand'] += (int) $location['onHand'];
                $converted = $this->convertedCache[MappingTypes::TYPE_LOCATION][$location['location']['id']] ?? false;

                if (false === $converted) {
                    $this->convertedCache[MappingTypes::TYPE_LOCATION][$location['location']['id']] = $this->converter->convert([
                        'type' => MappingTypes::TYPE_LOCATION,
                        'id' => $location['location']['id']
                    ]);
                }

                # replace with converted value
                if (null !== $this->convertedCache[MappingTypes::TYPE_LOCATION][$location['location']['id']]) {
                    $subject['locationStock'][$key]['location'] = $this->convertedCache[MappingTypes::TYPE_LOCATION][$location['location']['id']];
                    continue;
                }

                unset($subject['locationStock'][$key]);
            }
        }

        return $subject;
    }

    /**
     * @param array $subject
     *
     * @return array|null
     */
    protected function processVariation(array $subject): ?array
    {
        if(in_array(MappingTypes::TYPE_ATTRIBUTE, $this->disallowTypes)) {
            return $subject;
        }

        if (array_key_exists('variations', $subject)) {

            if ($subject['type'] === ProductInterface::SIMPLE_TYPE) {
                $subject['variations'] = null;
                return $subject;
            }

            if ($subject['type'] === ProductInterface::CONFIGURABLE_TYPE && (null === $subject['variations'] || count($subject['variations']) === 0)) {
                $this->errorMessage = 'Cannot export "Configurable" product without variation attributes.';
                return null;
            }


            foreach ($subject['variations'] as $key => $elem) {
                if (!is_array($elem) || array_key_exists('id', $elem) === false) {
                    unset($subject['variations'][$key]);
                    continue;
                }

                $converted = $this->convertedCache[MappingTypes::TYPE_ATTRIBUTE][$elem['id']] ?? false;
                if (false === $converted) {
                    $this->convertedCache[MappingTypes::TYPE_ATTRIBUTE][$elem['id']] = $this->converter->convert([
                        'type' => MappingTypes::TYPE_ATTRIBUTE,
                        'id' => $elem['id']
                    ]);
                }

                # replace with converted value
                if (null !== $this->convertedCache[MappingTypes::TYPE_ATTRIBUTE][$elem['id']]) {
                    $subject['variations'][$key] = $this->convertedCache[MappingTypes::TYPE_ATTRIBUTE][$elem['id']];
                    continue;
                }

                $this->errorMessage = sprintf('Cannot export product because variation attribute "%s" is not mapped.', $elem['name'] ?? $elem['id']);
                return null;
            }
        }

        return $subject;
    }

    /**
     * @param array $subject
     *
     * @return array|null
     */
    protected function processAttributes(array $subject): ?array
    {
        if(in_array(MappingTypes::TYPE_ATTRIBUTE, $this->disallowTypes)) {
            return $subject;
        }

        if (array_key_exists('attributes', $subject) && is_array($subject['attributes'])) {
            foreach ($subject['attributes'] as $key => $attribute) {
                if (!array_key_exists('attribute', $attribute) || !array_key_exists('id', $attribute['attribute'])) {
                    unset($subject['attributes'][$key]);
                    continue;
                }

                $converted = $this->convertedCache[MappingTypes::TYPE_ATTRIBUTE][$attribute['attribute']['id']] ?? false;
                if (false === $converted) {
                    $this->convertedCache[MappingTypes::TYPE_ATTRIBUTE][$attribute['attribute']['id']] = $this->converter->convert([
                        'type' => MappingTypes::TYPE_ATTRIBUTE,
                        'id' => $attribute['attribute']['id']
                    ]);
                }

                # replace with converted value
                if (null !== $this->convertedCache[MappingTypes::TYPE_ATTRIBUTE][$attribute['attribute']['id']]) {
                    $subject['attributes'][$key]['attribute'] = $this->convertedCache[MappingTypes::TYPE_ATTRIBUTE][$attribute['attribute']['id']];
                    continue;
                }

                unset($subject['attributes'][$key]);
            }
        }

        return $subject;
    }

    /**
     * @param array $subject
     *
     * @return array|null
     */
    protected function processClassification(array $subject): ?array
    {
        if(in_array(MappingTypes::TYPE_CLASSIFICATION, $this->disallowTypes)) {
            return $subject;
        }

        if (array_key_exists('classification', $subject) && is_array($subject['classification'])) {
            if (null === $subject['classification'] || (is_array($subject['classification']) && !array_key_exists('id', $subject['classification']))) {
                $subject['classification'] = null;
            }

            $converted = $this->convertedCache[MappingTypes::TYPE_CLASSIFICATION][$subject['classification']['id']] ?? false;
            if (false === $converted) {
                $this->convertedCache[MappingTypes::TYPE_CLASSIFICATION][$subject['classification']['id']] = $this->converter->convert([
                    'type' => MappingTypes::TYPE_CLASSIFICATION,
                    'id' => $subject['classification']['id']
                ]);
            }

            # replace with converted value
            if (null !== $this->convertedCache[MappingTypes::TYPE_CLASSIFICATION][$subject['classification']['id']]) {
                $subject['classification'] = $this->convertedCache[MappingTypes::TYPE_CLASSIFICATION][$subject['classification']['id']];
                return $subject;
            }

            $subject['classification'] = null;
        }

        return $subject;
    }

    /**
     * @param array $subject
     *
     * @return array|null
     */
    protected function processRelationship(array $subject): ?array
    {
        if (array_key_exists('relationship', $subject) && is_array($subject['relationship']) && $subject['type'] === ProductInterface::CONFIGURABLE_TYPE) {

            $attributes = [];
            foreach ($subject['relationship'] as $key => $elem) {
                if (!is_array($elem) || array_key_exists('id', $elem) === false) {
                    unset($subject['relationship'][$key]);
                }

                $converted = $this->convertedCache[MappingTypes::TYPE_ATTRIBUTE][$elem['id']] ?? false;
                if (false === $converted) {
                    $this->convertedCache[MappingTypes::TYPE_ATTRIBUTE][$elem['id']] = $this->converter->convert([
                        'type' => MappingTypes::TYPE_ATTRIBUTE,
                        'id' => $elem['id']
                    ]);
                }

                if (null === $this->convertedCache[MappingTypes::TYPE_ATTRIBUTE][$elem['id']]) {
                    $this->errorMessage = sprintf('Cannot export product because variation attribute "%s" is not mapped.', $elem['name'] ?? $elem['id']);
                    return null;
                }

                $attributes[] = $this->convertedCache[MappingTypes::TYPE_ATTRIBUTE][$elem['id']]['name'];
            }

            if (empty($attributes)) {
                $this->errorMessage = 'Cannot convert relationship where variation attributes are not mapped.';
                return null;
            }

            $convertedData = $this->converter->convert([
                'type' => MappingTypes::TYPE_RELATIONSHIP,
                'channel' => $this->channelId,
                'data' => [
                    'attributes' => $attributes
                ]
            ]);

            if (!is_array($convertedData) || array_key_exists('name', $convertedData) === false) {
                $this->errorMessage = sprintf('Cannot find relationship for the variation attributes "%s".', implode(', ', $attributes));
                return null;
            }

            $subject['relationship'] = $convertedData;
        }

        return $subject;
    }
}