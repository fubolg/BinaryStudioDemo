<?php declare(strict_types=1);

namespace BinaryStudioDemo\MappingBundle\Tests\Unit\Mapper;

use Doctrine\Persistence\ManagerRegistry;
use BinaryStudioDemo\CoreBundle\Context\Context;
use BinaryStudioDemo\ImportExportBundle\Context\StepExecutionContext;
use BinaryStudioDemo\MappingBundle\Converter\ConverterInterface;
use BinaryStudioDemo\MappingBundle\Mapper\ProductImportMapper;
use BinaryStudioDemo\MappingBundle\Provider\MappingDataProvider;
use BinaryStudioDemo\PlatformBundle\Mapping\Automapping\AutomappingBuilderInterface;
use PHPUnit\Framework\TestCase;

/**
 * Class ProductImportMapperTest
 * @package BinaryStudioDemo\MappingBundle\Tests\Unit\Mapper
 */
class ProductImportMapperTest extends TestCase
{
    /**
     * @var ProductImportMapper
     */
    private $mapper;

    /**
     * @var ConverterInterface
     */
    private $converter;

    /**
     * @var MappingDataProvider
     */
    private $automapping;

    /**
     * @var
     */
    private $mappingProvider;

    public function setUp(): void
    {
        $this->converter = self::getMockBuilder(ConverterInterface::class)->getMockForAbstractClass();
        $this->automapping = self::getMockBuilder(AutomappingBuilderInterface::class)->getMockForAbstractClass();
        $this->mappingProvider = self::getMockBuilder(MappingDataProvider::class)->disableOriginalConstructor()->getMock();

        $this->mapper = new ProductImportMapper($this->converter, $this->automapping, $this->mappingProvider);
    }

    public function test_should_support_import_direction()
    {
        $data = [
            'sku' => 'test',
            'type' => 'simple',
            'externalId' => 1
        ];
        $context = new Context();
        $context->setValue(StepExecutionContext::CHANNEL_ID, 1);
        $context->setValue('direction', 'import');

        $actual = $this->mapper->supports($data, $context);

        self::assertTrue($actual);
    }

    public function test_should_not_support_wrong_direction()
    {
        $data = [
            'sku' => 'test',
            'type' => 'simple',
            'externalId' => 1
        ];
        $context = new Context();
        $context->setValue(StepExecutionContext::CHANNEL_ID, 1);
        $context->setValue('direction', 'export');

        $actual = $this->mapper->supports($data, $context);

        self::assertFalse($actual);
    }

    public function test_should_do_nothing()
    {
        $this->mappingProvider->expects(self::never())->method('syncReplacements');
        $this->converter->expects(self::once())->method('setChannelId')->with(1);

        $data = [
            'sku' => 'test'
        ];
        $context = new Context();
        $context->setValue(StepExecutionContext::CHANNEL_ID, 1);
        $context->setValue('import', 'export');

        $actual = $this->mapper->run($data, $context);

        self::assertEquals($data, $actual);
    }

    public function test_should_change_attributes()
    {
        $this->mappingProvider->expects(self::never())->method('syncReplacements');

        $this->converter->expects(self::at(0))->method('setChannelId')->with(1);
        $this->converter->expects(self::at(1))->method('convert')->with(['channel' => 1, 'label' => 'Size', 'type' => 'attribute', 'data' => ['name' => 'Size']])->willReturn(['id' => 1]);
        $this->converter->expects(self::at(2))->method('convert')->with(['channel' => 1, 'label' => 'Color', 'type' => 'attribute', 'data' => ['name' => 'Color']])->willReturn(['id' => 2]);

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
        $context = new Context();
        $context->setValue(StepExecutionContext::CHANNEL_ID, 1);
        $context->setValue('direction', 'import');

        $actual = $this->mapper->run($data, $context);

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
        $this->mappingProvider->expects(self::never())->method('syncReplacements');

        $this->converter->expects(self::at(0))->method('setChannelId')->with(1);
        $this->converter->expects(self::at(2))->method('convert')->with(['channel' => 1, 'label' => 'Size', 'type' => 'attribute', 'data' => ['name' => 'Size']])->willReturn(['id' => 1]);
        $this->converter->expects(self::at(3))->method('convert')->with(['channel' => 1, 'label' => 'Color', 'type' => 'attribute', 'data' => ['name' => 'Color']])->willReturn(['id' => 2]);

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
        $context = new Context();
        $context->setValue(StepExecutionContext::CHANNEL_ID, 1);
        $context->setValue('direction', 'import');

        $actual = $this->mapper->run($data, $context);

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

    public function test_attributes_should_use_automap()
    {
        $this->automapping->expects(self::at(0))->method('build')->with([
            'attribute' => [
                ['label' => 'Size', 'name' => 'Size', 'id' => null]
            ]
        ])->willReturn(['attribute' => [ ['label' => 'Size', 'name' => 'Size', 'id' => null, 'referenceTo' => 1] ]]);
        $this->automapping->expects(self::at(1))->method('build')->with([
            'attribute' => [
                ['label' => 'Color', 'name' => 'Color', 'id' => null]
            ]]
        )->willReturn(['attribute' => [ ['label' => 'Color', 'name' => 'Color', 'id' => null, 'referenceTo' => 2] ]]);

        $this->mappingProvider->expects(self::at(0))->method('syncReplacements')->with([
            'attribute' => [
                ['label' => 'Size', 'name' => 'Size', 'id' => null, 'referenceTo' => 1]
            ]
        ], 1);
        $this->mappingProvider->expects(self::at(1))->method('syncReplacements')->with([
            'attribute' => [
                ['label' => 'Color', 'name' => 'Color', 'id' => null, 'referenceTo' => 2]
            ]
        ], 1);

        $this->converter->expects(self::at(0))->method('setChannelId')->with(1);

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

        $context = new Context();
        $context->setValue(StepExecutionContext::CHANNEL_ID, 1);
        $context->setValue('direction', 'import');

        $actual = $this->mapper->run($data, $context);

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

    public function test_should_change_classification()
    {
        $this->mappingProvider->expects(self::never())->method('syncReplacements');

        $this->converter->expects(self::at(0))->method('setChannelId')->with(1);
        $this->converter->expects(self::at(1))->method('convert')->with(['channel' => 1, 'label' => 'Shoes', 'type' => 'classification', 'data' => ['name' => 'Shoes']])->willReturn(['id' => 1]);

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
        $context = new Context();
        $context->setValue(StepExecutionContext::CHANNEL_ID, 1);
        $context->setValue('direction', 'import');

        $actual = $this->mapper->run($data, $context);

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

    public function test_classification_should_use_automap()
    {
        $this->automapping->expects(self::at(0))->method('build')->with([
            'classification' => [
                ['label' => 'Shoes', 'name' => 'Shoes', 'id' => null]
            ]
        ])->willReturn(['classification' => [ ['label' => 'Shoes', 'name' => 'Shoes', 'id' => null, 'referenceTo' => 1] ]]);
        $this->mappingProvider->expects(self::at(0))->method('syncReplacements')->with([
            'classification' => [
                ['label' => 'Shoes', 'name' => 'Shoes', 'id' => null, 'referenceTo' => 1]
            ]
        ], 1);

        $this->converter->expects(self::at(0))->method('setChannelId')->with(1);

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
        $context = new Context();
        $context->setValue(StepExecutionContext::CHANNEL_ID, 1);

//                  $this->mapper->allowAutomapping(true);
        $actual = $this->mapper->run($data, $context);

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
        $this->mappingProvider->expects(self::never())->method('syncReplacements');

        $this->converter->expects(self::at(0))->method('setChannelId')->with(1);
        $this->converter->expects(self::at(1))->method('convert')->with(['channel' => 1, 'label' => 'Size', 'type' => 'attribute', 'data' => ['name' => 'Size', 'is_configurable' => true]])->willReturn(['id' => 1]);

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
        $context = new Context();
        $context->setValue(StepExecutionContext::CHANNEL_ID, 1);
        $context->setValue('direction', 'import');

        $actual = $this->mapper->run($data, $context);

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
        $this->mappingProvider->expects(self::never())->method('syncReplacements');
        $this->converter->expects(self::at(0))->method('setChannelId')->with(1);

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
        $context = new Context();
        $context->setValue(StepExecutionContext::CHANNEL_ID, 1);
        $context->setValue('direction', 'import');

        $actual = $this->mapper->run($data, $context);

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
        $this->mappingProvider->expects(self::never())->method('syncReplacements');
        $this->converter->expects(self::at(0))->method('setChannelId')->with(1);

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
        $context = new Context();
        $context->setValue(StepExecutionContext::CHANNEL_ID, 1);
        $context->setValue('direction', 'import');

        $actual = $this->mapper->run($data, $context);

        self::assertEquals($data, $actual);
        self::assertEquals('No Variation attributes found in product, Variation attribute is required for "Configurable" products.', $this->mapper->getErrorMessage());
    }

    public function test_should_fail_when_unable_to_convert()
    {
        $this->mappingProvider->expects(self::never())->method('syncReplacements');
        $this->converter->expects(self::at(0))->method('setChannelId')->with(1);
        $this->converter->expects(self::at(1))->method('convert')->with(['channel' => 1, 'label' => 'Size', 'type' => 'attribute', 'data' => ['name' => 'Size', 'is_configurable' => true]])->willReturn(['id' => 1]);

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
        $context = new Context();
        $context->setValue(StepExecutionContext::CHANNEL_ID, 1);

        $actual = $this->mapper->run($data, $context);

        self::assertEquals([], $actual);
    }

    public function test_should_change_location()
    {
        $this->mappingProvider->expects(self::never())->method('syncReplacements');

        $this->converter->expects(self::at(0))->method('setChannelId')->with(1);
        $this->converter->expects(self::at(1))->method('convert')->with(['channel' => 1, 'label' => 'Size', 'type' => 'attribute', 'data' => ['name' => 'Size', 'is_configurable' => true]])->willReturn(['id' => 1]);
        $this->converter->expects(self::at(2))->method('convert')->with(['channel' => 1, 'label' => 'Ohio', 'type' => 'location', 'data' => ['name' => 'Ohio']])->willReturn(['id' => 10]);

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
                            'location' => 'Ohio',
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
        $context = new Context();
        $context->setValue(StepExecutionContext::CHANNEL_ID, 1);
        $context->setValue('direction', 'import');

        $actual = $this->mapper->run($data, $context);

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
                        [
                            'location' => ['id' => 10],
                            'onHand' => 12
                        ]
                    ]
                ],
                [
                    'sku' => 'test2',
                    'locationStock' => [
                        [
                            'location' => ['id' => 10],
                            'onHand' => 1
                        ]
                    ]
                ]
            ]
        ], $actual);
    }

    public function test_should_change_location_by_id()
    {
        $this->mappingProvider->expects(self::never())->method('syncReplacements');

        $this->converter->expects(self::at(0))->method('setChannelId')->with(1);
        $this->converter->expects(self::at(1))->method('convert')->with(['channel' => 1, 'label' => 'Size', 'type' => 'attribute', 'data' => ['name' => 'Size', 'is_configurable' => true]])->willReturn(['id' => 1]);
        $this->converter->expects(self::at(2))->method('convert')->with(['channel' => 1, 'label' => 0, 'type' => 'location', 'data' => ['id' => 0]])->willReturn(['id' => 10]);

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
                            'location' => 0,
                            'onHand' => 12
                        ],
                        [
                            'location' => 1,
                            'onHand' => 10
                        ]
                    ]
                ],
                [
                    'sku' => 'test2',
                    'locationStock' => [
                        [
                            'location' => 0,
                            'onHand' => 1
                        ],
                        [
                            'location' => 1,
                            'onHand' => 2
                        ]
                    ]
                ]
            ]
        ];
        $context = new Context();
        $context->setValue(StepExecutionContext::CHANNEL_ID, 1);
        $context->setValue('direction', 'import');

        $actual = $this->mapper->run($data, $context);

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
                        [
                            'location' => ['id' => 10],
                            'onHand' => 12
                        ]
                    ]
                ],
                [
                    'sku' => 'test2',
                    'locationStock' => [
                        [
                            'location' => ['id' => 10],
                            'onHand' => 1
                        ]
                    ]
                ]
            ]
        ], $actual);
    }
}