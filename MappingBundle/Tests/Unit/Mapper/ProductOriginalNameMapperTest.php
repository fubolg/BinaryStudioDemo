<?php declare(strict_types=1);

namespace BinaryStudioDemo\MappingBundle\Tests\Unit\Mapper;

use Doctrine\ORM\EntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use BinaryStudioDemo\CoreBundle\Context\Context;
use BinaryStudioDemo\ImportExportBundle\Interfaces\StepExecutionContextInterface;
use BinaryStudioDemo\MappingBundle\Mapper\ProductOriginalNameMapper;
use BinaryStudioDemo\PlatformBundle\Entity\ProductVariantAttribute;
use BinaryStudioDemo\PlatformBundle\Entity\ProductVariantClassification;
use BinaryStudioDemo\PlatformBundle\Entity\WarehouseLocation;
use PHPUnit\Framework\TestCase;

/**
 * Class ProductOriginalNameMapperTest
 * @package BinaryStudioDemo\MappingBundle\Tests\Unit\Mapper
 */
class ProductOriginalNameMapperTest extends TestCase {

    private $mapper;
    private $managerRegistry;
    private $repo;
    private $context;

    public function setUp(): void
    {
        $this->repo = self::getMockBuilder(EntityRepository::class)->disableOriginalConstructor()->getMock();
        $this->managerRegistry = self::getMockBuilder(ManagerRegistry::class)->disableOriginalConstructor()->getMock();
        $this->managerRegistry->expects(self::any())->method('getRepository')->willReturn($this->repo);

        $this->mapper = new ProductOriginalNameMapper($this->managerRegistry);

        $this->context = new Context();
        $this->context->setValue(StepExecutionContextInterface::CHANNEL_ID, 0);
    }

    public function test_should_do_nothing()
    {
        $this->repo->expects(self::never())->method('findAll');

        $data = [
            'sku' => 'test'
        ];

        $context = new Context();
        $context->setValue(StepExecutionContextInterface::CHANNEL_ID, 1);

        $actual = $this->mapper->run($data, $context);

        self::assertEquals($data, $actual);
    }

    public function test_should_change_attributes()
    {
        $data = [
            'sku' => 'test',
            'type' => 'simple',
            'attributes' => [
                [
                    'attribute' => 'Size',
                    'value' => 'XL'
                ],
                [
                    'attribute' => 'Color',
                    'value' => 'Red'
                ],
                [
                    'attribute' => 'UPC',
                    'value' => ''
                ],
                [
                    'attribute' => 'Enabled',
                    'value' => true
                ]
            ]
        ];

        $this->repo->expects(self::once())->method('findAll')->willReturn([
            $this->getAttribute(1, 'size'),
            $this->getAttribute(2, 'color')
        ]);

        $actual = $this->mapper->run($data, $this->context);

        self::assertEquals([
            'sku' => 'test',
            'type' => 'simple',
            'attributes' => [
                [
                    'attribute' => ['id' => 1],
                    'value' => 'XL'
                ],
                [
                    'attribute' => ['id' => 2],
                    'value' => 'Red'
                ]
            ]
        ], $actual);
    }

    public function test_should_change_attributes_of_childs()
    {
        $data = [
            'sku' => 'test',
            'type' => 'configurable',
            'variants' => [
                [
                    'sku' => 'test1',
                    'attributes' => [
                        [
                            'attribute' => 'Size',
                            'value' => 'XL'
                        ]
                    ]
                ],
                [
                    'sku' => 'test2',
                    'attributes' => [
                        [
                            'attribute' => 'Color',
                            'value' => 'Red'
                        ],
                        [
                            'attribute' => 'UPC',
                            'value' => ''
                        ]
                    ]
                ]
            ],
            'attributes' => [
                [
                    'attribute' => 'Enabled',
                    'value' => true
                ]
            ]
        ];

        $this->repo->expects(self::once())->method('findAll')->willReturn([
            $this->getAttribute(1, 'size'),
            $this->getAttribute(2, 'color')
        ]);

        $actual = $this->mapper->run($data, $this->context);

        self::assertEquals([
            'sku' => 'test',
            'type' => 'configurable',
            'variants' => [
                [
                    'sku' => 'test1',
                    'attributes' => [
                        [
                            'attribute' => ['id' => 1],
                            'value' => 'XL'
                        ]
                    ]
                ],
                [
                    'sku' => 'test2',
                    'attributes' => [
                        [
                            'attribute' => ['id' => 2],
                            'value' => 'Red'
                        ]
                    ]
                ]
            ],
            'attributes' => []
        ], $actual);
    }

    public function test_should_change_classification()
    {
        $data = [
            'sku' => 'test',
            'type' => 'configurable',
            'classification' => 'Shoes',
            'variants' => [
                [
                    'sku' => 'test1',
                    'classification' => 'Shoes'
                ]
            ]
        ];

        $this->repo->expects(self::once())->method('findAll')->willReturn([
            $this->getClassification(1, 'shoes'),
            $this->getClassification(2, 'hats')
        ]);

        $actual = $this->mapper->run($data, $this->context);

        self::assertEquals([
            'sku' => 'test',
            'type' => 'configurable',
            'classification' => ['id' => 1],
            'variants' => [
                [
                    'sku' => 'test1',
                    'classification' => ['id' => 1]
                ]
            ]
        ], $actual);
    }

    public function test_should_change_variation()
    {
        $data = [
            'sku' => 'test',
            'type' => 'configurable',
            'variations' => [
                'Size'
            ],
            'variants' => [
                [
                    'sku' => 'test1'
                ]
            ]
        ];

        $this->repo->expects(self::once())->method('findAll')->willReturn([
            $this->getAttribute(1, 'size'),
            $this->getAttribute(2, 'color')
        ]);

        $actual = $this->mapper->run($data, $this->context);

        self::assertEquals([
            'sku' => 'test',
            'type' => 'configurable',
            'variations' => [
                ['id' => 1]
            ],
            'variants' => [
                [
                    'sku' => 'test1'
                ]
            ]
        ], $actual);
    }

    public function test_should_clear_variations_on_simpe_product()
    {
        $data = [
            'sku' => 'test',
            'type' => 'simple',
            'variations' => [
                'Size'
            ],
            'variants' => [
                [
                    'sku' => 'test1'
                ]
            ]
        ];

        $this->repo->expects(self::never())->method('findAll');

        $actual = $this->mapper->run($data, $this->context);

        self::assertEquals([
            'sku' => 'test',
            'type' => 'simple',
            'variations' => [],
            'variants' => [
                [
                    'sku' => 'test1'
                ]
            ]
        ], $actual);
    }

    public function test_should_fail_when_no_variations()
    {
        $data = [
            'sku' => 'test',
            'type' => 'configurable',
            'variations' => [],
            'variants' => [
                [
                    'sku' => 'test1'
                ]
            ]
        ];

        $this->repo->expects(self::never())->method('findAll');

        $actual = $this->mapper->run($data, $this->context);

        self::assertEquals([], $actual);
        self::assertEquals('No Variation attributes found in product, Variation attribute is required for "Configurable" products.', $this->mapper->getErrorMessage());
    }

    public function test_should_fail_when_unable_to_convert()
    {
        $data = [
            'sku' => 'test',
            'type' => 'configurable',
            'variations' => [
                'Size',
                'Color'
            ],
            'variants' => [
                [
                    'sku' => 'test1'
                ]
            ]
        ];

        $this->repo->expects(self::once())->method('findAll')->willReturn([
            $this->getAttribute(2, 'color')
        ]);

        $actual = $this->mapper->run($data, $this->context);

        self::assertEquals([], $actual);
    }

    public function test_should_change_location()
    {
        $data = [
            'sku' => 'test',
            'type' => 'configurable',
            'variations' => [
                'Size'
            ],
            'variants' => [
                [
                    'sku' => 'test1',
                    'locationStock' => [
                        [
                            'location' => 'Nebraska',
                            'onHand' => 12
                        ],
                        [
                            'location' => 'Belvue',
                            'onHand' => 10
                        ]
                    ]
                ],
                [
                    'sku' => 'test2',
                    'locationStock' => [
                        [
                            'location' => 'Ohio',
                            'onHand' => 1
                        ],
                        [
                            'location' => 'Belvue',
                            'onHand' => 2
                        ]
                    ]
                ]
            ]
        ];

        $this->repo->expects(self::at(0))->method('findAll')->willReturn([
            $this->getAttribute(1, 'size'),
            $this->getAttribute(2, 'color'),
        ]);

        $this->repo->expects(self::at(1))->method('findAll')->willReturn([
            $this->getLocation(10, 'Belvue'),
            $this->getLocation(11, 'ohio'),
        ]);

        $actual = $this->mapper->run($data, $this->context);

        self::assertEquals([
            'sku' => 'test',
            'type' => 'configurable',
            'variations' => [
                ['id' => 1]
            ],
            'variants' => [
                [
                    'sku' => 'test1',
                    'locationStock' => [
                        1 => [
                            'location' => ['id' => 10],
                            'onHand' => 10
                        ]
                    ]
                ],
                [
                    'sku' => 'test2',
                    'locationStock' => [
                        [
                            'location' => ['id' => 11],
                            'onHand' => 1
                        ],
                        [
                            'location' => ['id' => 10],
                            'onHand' => 2
                        ]
                    ]
                ]
            ]
        ], $actual);
    }

    /**
     * @param int    $id
     * @param string $name
     *
     * @return ProductVariantAttribute
     */
    private function getAttribute(int $id, string $name): ProductVariantAttribute
    {
        $attribute = new ProductVariantAttribute();
        $attribute->setId($id);
        $attribute->setName($name);

        return $attribute;
    }

    /**
     * @param int    $id
     * @param string $name
     *
     * @return ProductVariantClassification
     */
    private function getClassification(int $id, string $name): ProductVariantClassification
    {
        $classification = new ProductVariantClassification();
        $classification->setId($id);
        $classification->setName($name);

        return $classification;
    }

    /**
     * @param int    $id
     * @param string $name
     *
     * @return WarehouseLocation
     */
    private function getLocation(int $id, string $name): WarehouseLocation
    {
        $location = new WarehouseLocation();
        $location->setId($id);
        $location->setName($name);

        return $location;
    }
}