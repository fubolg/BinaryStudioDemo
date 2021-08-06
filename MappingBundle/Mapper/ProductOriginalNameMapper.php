<?php declare(strict_types=1);

namespace BinaryStudioDemo\MappingBundle\Mapper;

use Doctrine\Persistence\ManagerRegistry;
use BinaryStudioDemo\CoreBundle\Interfaces\ContextInterface;
use BinaryStudioDemo\ImportExportBundle\Interfaces\StepExecutionContextInterface;
use BinaryStudioDemo\IntegrationBundle\Traits\ErrorMessageAwaredTrait;
use BinaryStudioDemo\MappingBundle\Interfaces\MapperInterface;
use BinaryStudioDemo\MappingBundle\MappingTypes;
use BinaryStudioDemo\PlatformBundle\Entity\ProductVariantAttribute;
use BinaryStudioDemo\PlatformBundle\Entity\ProductVariantClassification;
use BinaryStudioDemo\PlatformBundle\Entity\ShippingCarrier;
use BinaryStudioDemo\PlatformBundle\Entity\WarehouseLocation;
use BinaryStudioDemo\ProductBundle\Interfaces\ProductInterface;

/**
 * Class ProductOriginalNameMapper
 * @package BinaryStudioDemo\MappingBundle\Mapper
 */
class ProductOriginalNameMapper implements MapperInterface
{
    use ErrorMessageAwaredTrait;

    private ManagerRegistry $managerRegistry;
    private array $schema = [];

    /**
     * ProductOriginalNameMapper constructor.
     *
     * @param ManagerRegistry $managerRegistry
     */
    public function __construct(ManagerRegistry $managerRegistry)
    {
        $this->managerRegistry = $managerRegistry;
    }

    /**
     * @param array $subject
     * @param ContextInterface $context
     *
     * @return bool
     */
    public function supports(array $subject, ContextInterface $context): bool
    {
        $channelId = $context->getValue(StepExecutionContextInterface::CHANNEL_ID);
        if(null !== $channelId && (int) $channelId === 0) {
            return array_key_exists('sku', $subject);
        }

        return false;
    }

    /**
     * @param array            $product
     * @param ContextInterface $context
     *
     * @return array|null
     */
    public function run(array $product, ContextInterface $context): ?array
    {
        $this->resetErrorMessage();

        $convertedProduct = $this->processVariation($product);
        if (null === $convertedProduct) return [];
        $convertedProduct = $this->processClassification($convertedProduct);
        $convertedProduct = $this->processAttributes($convertedProduct);

        if (array_key_exists('variants', $convertedProduct)) {
            foreach ($convertedProduct['variants'] as &$variant) {
                $variant = $this->processClassification($variant);
                $variant = $this->processAttributes($variant);
                $variant = $this->processLocation($variant);
            }
        }

        return $convertedProduct;
    }

    /**
     * @param array $subject
     *
     * @return array|null
     */
    protected function processLocation(array $subject): ?array
    {
        if (array_key_exists('locationStock', $subject) && !empty($subject['locationStock'])) {
            foreach ($subject['locationStock'] as $k => &$location) {
                if (array_key_exists('location', $location)) {
                    $result = $this->convert((string)$location['location'], MappingTypes::TYPE_LOCATION);
                    # replace with converted value
                    if (null !== $result) {
                        $location['location'] = $result;
                        continue;
                    }
                }

                unset($subject['locationStock'][$k]);
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
        if (array_key_exists('variations', $subject)) {

            $type = $subject['type'] ?? null;

            if (empty($subject['variations']) && $type === ProductInterface::CONFIGURABLE_TYPE) {
                $this->errorMessage = 'No Variation attributes found in product, Variation attribute is required for "Configurable" products.';
                return null;
            }

            if ($subject['type'] !== ProductInterface::CONFIGURABLE_TYPE) {
                $subject['variations'] = [];
                return $subject;
            }

            foreach ($subject['variations'] as $key => &$variation) {
                $result = $this->convert((string)$variation, MappingTypes::TYPE_ATTRIBUTE);
                # replace with converted value
                if (null !== $result) {
                    $subject['variations'][$key] = $result;
                    continue;
                }

                $this->errorMessage = sprintf('Variation attribute "%s" cannot be converted, attribute not found in BinaryStudioDemo.', $variation);
                return null;
            }
        }

        return $subject;
    }

    /**
     * @param string $name
     * @param string $type
     *
     * @return array|null
     */
    private function convert(string $name, string $type): ?array
    {
        if(null === ($this->schema[$type] ?? null)) {
            $this->schema[$type] = $this->loadSchema($type);
        }

        return $this->schema[$type][strtolower($name)] ?? null;
    }

    /**
     * @param string $type
     *
     * @return array
     */
    private function loadSchema(string $type): array {
        $types = [
            MappingTypes::TYPE_ATTRIBUTE => ProductVariantAttribute::class,
            MappingTypes::TYPE_CLASSIFICATION => ProductVariantClassification::class,
            MappingTypes::TYPE_LOCATION => WarehouseLocation::class,
            MappingTypes::TYPE_CARRIER => ShippingCarrier::class
        ];

        $className = $types[$type] ?? null;
        if(null === $className) return [];

        $repo = $this->managerRegistry->getRepository($className);
        $all = $repo->findAll();

        $results = [];
        if(is_array($all) && !empty($all)) {
            foreach($all as $item) {
                $results[strtolower($item->getName())] = ['id' => $item->getId()];
            }
        }

        return $results;
    }

    /**
     * @param array $subject
     *
     * @return array|null
     */
    protected function processClassification(array $subject): ?array
    {
        if (array_key_exists('classification', $subject)) {
            if (((is_string($subject['classification']) && $subject['classification'] !== '') || is_int($subject['classification']))) {
                $result = $this->convert($subject['classification'], MappingTypes::TYPE_CLASSIFICATION);

                # replace with converted value
                if (null !== $result) {
                    $subject['classification'] = $result;
                    return $subject;
                }
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
    protected function processAttributes(array $subject): ?array
    {
        if (array_key_exists('attributes', $subject) && !empty($subject['attributes'])) {
            foreach ($subject['attributes'] as $k => $attribute) {
                if (array_key_exists('attribute', $attribute) && is_string($attribute['attribute'])) {

                    $result = $this->convert($attribute['attribute'], MappingTypes::TYPE_ATTRIBUTE);

                    # replace with converted value
                    if (null !== $result) {
                        $subject['attributes'][$k]['attribute'] = $result;
                        continue;
                    }
                }

                unset($subject['attributes'][$k]);
            }
        }

        return $subject;
    }
}