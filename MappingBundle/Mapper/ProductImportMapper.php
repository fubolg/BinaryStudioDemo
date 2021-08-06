<?php declare(strict_types=1);

namespace BinaryStudioDemo\MappingBundle\Mapper;

use BinaryStudioDemo\CoreBundle\Interfaces\ContextInterface;
use BinaryStudioDemo\ImportExportBundle\Interfaces\StepExecutionContextInterface;
use BinaryStudioDemo\MappingBundle\Interfaces\MapperInterface;
use BinaryStudioDemo\MappingBundle\MappingTypes;
use BinaryStudioDemo\ProductBundle\Interfaces\ProductInterface;

/**
 * Class ProductImportMapper
 * @package BinaryStudioDemo\MappingBundle\Mapper
 */
class ProductImportMapper extends AbstractMapper implements MapperInterface
{
    /**
     * @param array $subject
     * @param ContextInterface $context
     *
     * @return bool
     */
    public function supports(array $subject, ContextInterface $context): bool
    {
        // the import and export looks same!
        $direction = $context->getValue('direction');
        if($direction !== 'import') {
            return false;
        }

        return array_key_exists('sku', $subject) &&
            array_key_exists('externalId', $subject);
    }

    /**
     * @param array $product
     * @param ContextInterface $context
     * @return array|null
     */
    public function run(array $product, ContextInterface $context): ?array
    {
        # initialize
        $this->channelId = (int) $context->getValue(StepExecutionContextInterface::CHANNEL_ID);
        $this->converter->setChannelId($this->channelId);

        $this->resetErrorMessage();

        ##### Convert product

//        $convertedProduct = $this->processRelationship($product);
//        if (null === $convertedProduct) return [];
        $convertedProduct = $this->processVariation($product);
        if (null === $convertedProduct) return [];
        $convertedProduct = $this->processClassification($convertedProduct);
        $convertedProduct = $this->processAttributes($convertedProduct);

        // when we got variant we have to process locations as well
        $convertedProduct = $this->processLocation($convertedProduct);

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

                    # convert
                    $converted = $this->convertedCache[MappingTypes::TYPE_LOCATION][(string)$location['location']] ?? false;
                    if (false === $converted) {

                        $data = [];
                        if (is_numeric($location['location'])) {
                            $data['id'] = (string) $location['location'];
                        } else {
                            $data['name'] = $location['location'];
                        }

                        $this->convertedCache[MappingTypes::TYPE_LOCATION][(string)$location['location']] = $this->converter->convert(
                            [
                                'channel' => $this->channelId,
                                'label' => (string)$location['location'],
                                'type' => MappingTypes::TYPE_LOCATION,
                                'data' => $data
                            ]
                        );
                    }

                    # automap
//                    $this->automap(MappingTypes::TYPE_LOCATION, $location['location']);

                    $result = $this->convertedCache[MappingTypes::TYPE_LOCATION][(string)$location['location']] ?? null;
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
        if (array_key_exists('variations', $subject) && is_array($subject['variations'])) {

            $type = $subject['type'] ?? null;

            if (empty($subject['variations']) && $type === ProductInterface::CONFIGURABLE_TYPE) {
                $this->errorMessage = 'No Variation attributes found in product, Variation attribute is required for "Configurable" products.';
                return $subject;
            }

            if ($subject['type'] !== ProductInterface::CONFIGURABLE_TYPE) {
                $subject['variations'] = [];
                return $subject;
            }

            foreach ($subject['variations'] as $key => &$variation) {
                # convert
                $converted = $this->convertedCache[MappingTypes::TYPE_ATTRIBUTE][$variation] ?? false;
                if (false === $converted) {
                    $this->convertedCache[MappingTypes::TYPE_ATTRIBUTE][$variation] = $this->converter->convert(
                        [
                            'channel' => $this->channelId,
                            'label' => (string)$variation,
                            'type' => MappingTypes::TYPE_ATTRIBUTE,
                            'data' => [
                                'name' => (string)$variation,
                                'is_configurable' => true
                            ]
                        ]
                    );
                }

                # automap
                $this->automap(MappingTypes::TYPE_ATTRIBUTE, $variation, ['is_configurable' => true]);

                $result = $this->convertedCache[MappingTypes::TYPE_ATTRIBUTE][$variation] ?? null;

                # replace with converted value
                if (null !== $result) {
                    $subject['variations'][$key] = $result;
                    continue;
                } else {
                    $result['variations'][$key] = $variation;
                }

                $this->errorMessage = sprintf('Variation attribute "%s" cannot be converted, follow channels variations mapping tab and choose proper variation attribute.', $variation);
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
    protected function processClassification(array $subject): ?array
    {
        if (array_key_exists('classification', $subject)) {
            if (((is_string($subject['classification']) && $subject['classification'] !== '') || is_int($subject['classification']))) {
                # convert
                $converted = $this->convertedCache[MappingTypes::TYPE_CLASSIFICATION][$subject['classification']] ?? false;
                if (false === $converted) {
                    $data = [];
                    if (is_int($subject['classification'])) {
                        $data['id'] = $subject['classification'];
                    } else {
                        $data['name'] = $subject['classification'];
                    }

                    $this->convertedCache[MappingTypes::TYPE_CLASSIFICATION][$subject['classification']] = $this->converter->convert(
                        [
                            'channel' => $this->channelId,
                            'label' => $subject['classification'],
                            'type' => MappingTypes::TYPE_CLASSIFICATION,
                            'data' => $data
                        ]
                    );
                }

                # automap
                $this->automap(MappingTypes::TYPE_CLASSIFICATION, $subject['classification']);

                $result = $this->convertedCache[MappingTypes::TYPE_CLASSIFICATION][$subject['classification']] ?? null;
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
                    # convert
                    $converted = $this->convertedCache[MappingTypes::TYPE_ATTRIBUTE][$attribute['attribute']] ?? false;

                    if (false === $converted) {
                        $this->convertedCache[MappingTypes::TYPE_ATTRIBUTE][$attribute['attribute']] = $this->converter->convert(
                            [
                                'channel' => $this->channelId,
                                'label' => $attribute['attribute'],
                                'type' => MappingTypes::TYPE_ATTRIBUTE,
                                'data' => [
                                    'name' => $attribute['attribute']
                                ]
                            ]
                        );
                    }

                    # automap
                    $this->automap(MappingTypes::TYPE_ATTRIBUTE, $attribute['attribute']);

                    $result = $this->convertedCache[MappingTypes::TYPE_ATTRIBUTE][$attribute['attribute']] ?? null;
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