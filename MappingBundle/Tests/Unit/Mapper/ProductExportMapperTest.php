<?php declare(strict_types=1);

namespace BinaryStudioDemo\MappingBundle\Tests\Unit\Mapper;

use BinaryStudioDemo\CoreBundle\Context\Context;
use BinaryStudioDemo\ImportExportBundle\Context\StepExecutionContext;
use BinaryStudioDemo\MappingBundle\Converter\ConverterInterface;
use BinaryStudioDemo\MappingBundle\Mapper\ProductExportMapper;
use BinaryStudioDemo\MappingBundle\Mapper\ProductImportMapper;
use PHPUnit\Framework\TestCase;

/**
 * Class ProductExportMapperTest
 * @package BinaryStudioDemo\MappingBundle\Tests\Unit\Mapper
 */
class ProductExportMapperTest extends TestCase
{
    /**
     * @var ProductImportMapper
     */
    private $mapper;

    /**
     * @var ConverterInterface
     */
    private $converter;

    public function setUp(): void
    {
        $this->converter = self::getMockBuilder(ConverterInterface::class)->getMockForAbstractClass();
        $this->mapper = new ProductExportMapper($this->converter);
    }

    public function test_should_support_export_direction()
    {
        $data = [
            'sku' => 'test',
            'type' => 'simple'
        ];
        $context = new Context();
        $context->setValue(StepExecutionContext::CHANNEL_ID, 1);
        $context->setValue('direction', 'export');

        $actual = $this->mapper->supports($data, $context);

        self::assertTrue($actual);
    }

    public function test_should_not_support_wrong_direction()
    {
        $data = [
            'sku' => 'test',
            'type' => 'simple'
        ];
        $context = new Context();
        $context->setValue(StepExecutionContext::CHANNEL_ID, 1);
        $context->setValue('direction', 'import');

        $actual = $this->mapper->supports($data, $context);

        self::assertFalse($actual);
    }

    public function test_should_do_nothing()
    {
        $this->converter->expects(self::once())->method('setChannelId')->with(1);

        $data = [
            'sku' => 'test'
        ];

        $context = new Context();
        $context->setValue(StepExecutionContext::CHANNEL_ID, 1);
        $context->setValue('direction', 'export');

        $actual = $this->mapper->run($data, $context);

        self::assertEquals($data, $actual);
    }

    public function test_should_change_attributes()
    {
        $this->converter->expects(self::at(0))->method('setChannelId')->with(1);
        $this->converter->expects(self::at(1))->method('convert')->with(['type' => 'attribute', 'id' => 1])->willReturn(['id' => '122', 'name' => 'ChannelSize']);
        $this->converter->expects(self::at(2))->method('convert')->with(['type' => 'attribute', 'id' => 2])->willReturn(['id' => '133', 'name' => 'ChannelColor']);

        $data = [
            'sku' => 'test',
            'type' => 'simple',
            'attributes' => [
                [
                    'attribute' => [
                        'id' => 1,
                        'name' => 'Size',
                    ],
                    'value' => 'XL'
                ],
                [
                    'attribute' => [
                        'id' => 2,
                        'name' => 'Color',
                    ],
                    'value' => 'Red'
                ],
                [
                    'attribute' => [
                        'id' => 3,
                        'name' => 'UPC',
                    ],
                    'value' => ''
                ],
                [
                    'attribute' => [
                        'id' => 4,
                        'name' => 'Enabled',
                    ],
                    'value' => true
                ]
            ]
        ];
        $context = new Context();
        $context->setValue(StepExecutionContext::CHANNEL_ID, 1);
        $context->setValue('direction', 'export');

        $actual = $this->mapper->run($data, $context);

        self::assertEquals([
            'sku' => 'test',
            'type' => 'simple',
            'attributes' => [
                [
                    'attribute' => ['id' => '122', 'name' => 'ChannelSize'],
                    'value' => 'XL'
                ],
                [
                    'attribute' => ['id' => '133', 'name' => 'ChannelColor'],
                    'value' => 'Red'
                ]
            ]
        ], $actual);
    }

    public function test_should_change_attributes_of_childs()
    {
        $this->converter->expects(self::at(0))->method('setChannelId')->with(1);
        $this->converter->expects(self::at(1))->method('convert')->with(['type' => 'attribute', 'id' => 1])->willReturn(['id' => '122', 'name' => 'ChannelSize']);
        $this->converter->expects(self::at(3))->method('convert')->with(['type' => 'attribute', 'id' => 2])->willReturn(['id' => '133', 'name' => 'ChannelColor']);

        $data = [
            'sku' => 'test',
            'type' => 'configurable',
            'variants' => [
                [
                    'sku' => 'test1',
                    'attributes' => [
                        [
                            'attribute' => [
                                'id' => 1,
                                'name' => 'Size',
                            ],
                            'value' => 'XL'
                        ]
                    ]
                ],
                [
                    'sku' => 'test2',
                    'attributes' => [
                        [
                            'attribute' => [
                                'id' => 2,
                                'name' => 'Color',
                            ],
                            'value' => 'Red'
                        ],
                        [
                            'attribute' => [
                                'id' => 3,
                                'name' => 'UPC',
                            ],
                            'value' => ''
                        ]
                    ]
                ]
            ],
            'attributes' => [
                [
                    'attribute' => [
                        'id' => 1,
                        'name' => 'Size',
                    ],
                    'value' => 'XL'
                ],
                [
                    'attribute' => [
                        'id' => 4,
                        'name' => 'Enabled',
                    ],
                    'value' => true
                ]
            ]
        ];
        $context = new Context();
        $context->setValue(StepExecutionContext::CHANNEL_ID, 1);
        $context->setValue('direction', 'export');

        $actual = $this->mapper->run($data, $context);
        self::assertEquals([
            'sku' => 'test',
            'type' => 'configurable',
            'variants' => [
                [
                    'sku' => 'test1',
                    'attributes' => [
                        [
                            'attribute' => ['id' => '122', 'name' => 'ChannelSize'],
                            'value' => 'XL'
                        ]
                    ]
                ],
                [
                    'sku' => 'test2',
                    'attributes' => [
                        [
                            'attribute' => ['id' => '133', 'name' => 'ChannelColor'],
                            'value' => 'Red'
                        ]
                    ]
                ]
            ],
            'attributes' => [
                [
                    'attribute' => ['id' => '122', 'name' => 'ChannelSize'],
                    'value' => 'XL'
                ]
            ]
        ], $actual);
    }

    public function test_should_change_classification()
    {
        $this->converter->expects(self::at(0))->method('setChannelId')->with(1);
        $this->converter->expects(self::at(1))->method('convert')->with(['type' => 'classification', 'id' => 1])->willReturn(['id' => null, 'name' => 'ChannelShoes']);

        $data = [
            'sku' => 'test',
            'type' => 'configurable',
            'classification' => ['id' => 1, 'name' => 'Shoes'],
            'variants' => [
                [
                    'sku' => 'test1',
                    'classification' => ['id' => 1, 'name' => 'Shoes'],
                ]
            ]
        ];
        $context = new Context();
        $context->setValue(StepExecutionContext::CHANNEL_ID, 1);
        $context->setValue('direction', 'export');

        $actual = $this->mapper->run($data, $context);

        self::assertEquals([
            'sku' => 'test',
            'type' => 'configurable',
            'classification' => ['id' => null, 'name' => 'ChannelShoes'],
            'variants' => [
                [
                    'sku' => 'test1',
                    'classification' => ['id' => null, 'name' => 'ChannelShoes']
                ]
            ]
        ], $actual);
    }

    public function test_should_change_variation()
    {
        $this->converter->expects(self::at(0))->method('setChannelId')->with(1);
        $this->converter->expects(self::at(1))->method('convert')->with(['type' => 'attribute', 'id' => 1])->willReturn(['id' => '11', 'name' => 'VariationSize']);

        $data = [
            'sku' => 'test',
            'type' => 'configurable',
            'variations' => [
                ['id' => 1, 'name' => 'Size']
            ],
            'variants' => [
                [
                    'sku' => 'test1'
                ]
            ]
        ];
        $context = new Context();
        $context->setValue(StepExecutionContext::CHANNEL_ID, 1);
        $context->setValue('direction', 'export');

        $actual = $this->mapper->run($data, $context);

        self::assertEquals([
            'sku' => 'test',
            'type' => 'configurable',
            'variations' => [
                ['id' => '11', 'name' => 'VariationSize']
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
        $this->converter->expects(self::at(0))->method('setChannelId')->with(1);

        $data = [
            'sku' => 'test',
            'type' => 'simple',
            'variations' => [
                ['id' => 1, 'name' => 'Size']
            ],
            'variants' => [
                [
                    'sku' => 'test1'
                ]
            ]
        ];
        $context = new Context();
        $context->setValue(StepExecutionContext::CHANNEL_ID, 1);
        $context->setValue('direction', 'export');

        $actual = $this->mapper->run($data, $context);

        self::assertEquals([
            'sku' => 'test',
            'type' => 'simple',
            'variations' => null,
            'variants' => [
                [
                    'sku' => 'test1'
                ]
            ]
        ], $actual);
    }

    public function test_should_fail_when_no_variations()
    {
        $this->converter->expects(self::at(0))->method('setChannelId')->with(1);

        $data = [
            'sku' => 'test',
            'type' => 'configurable',
            'variations' => null,
            'variants' => [
                [
                    'sku' => 'test1'
                ]
            ]
        ];
        $context = new Context();
        $context->setValue(StepExecutionContext::CHANNEL_ID, 1);
        $context->setValue('direction', 'export');

        $actual = $this->mapper->run($data, $context);

        self::assertEquals([], $actual);
        self::assertEquals('Cannot export "Configurable" product without variation attributes.', $this->mapper->getErrorMessage());
    }

    public function test_should_fail_when_unable_to_convert()
    {
        $this->converter->expects(self::at(0))->method('setChannelId')->with(1);
        $this->converter->expects(self::at(1))->method('convert')->with(['type' => 'attribute', 'id' => 1])->willReturn(['id' => '11', 'name' => 'VariationSize']);

        $data = [
            'sku' => 'test',
            'type' => 'configurable',
            'variations' => [
                [
                    'id' => 1,
                    'name' => 'Size'
                ],
                [
                    'id' => 2,
                    'name' => 'Color'
                ]
            ],
            'variants' => [
                [
                    'sku' => 'test1'
                ]
            ]
        ];

        $context = new Context();
        $context->setValue(StepExecutionContext::CHANNEL_ID, 1);
        $context->setValue('direction', 'export');

        $actual = $this->mapper->run($data, $context);

        self::assertEquals([], $actual);
        self::assertEquals('Cannot export product because variation attribute "Color" is not mapped.', $this->mapper->getErrorMessage());
    }

    public function test_should_change_location()
    {
        $this->converter->expects(self::at(0))->method('setChannelId')->with(1);
        $this->converter->expects(self::at(1))->method('convert')->with(['type' => 'attribute', 'id' => 1])->willReturn(['id' => '11', 'name' => 'VariationSize']);
        $this->converter->expects(self::at(2))->method('convert')->with(['type' => 'location', 'id' => 1])->willReturn(['id' => '10', 'name' => 'ChannelOhio']);

        $data = [
            'sku' => 'test',
            'type' => 'configurable',
            'variations' => [
                ['id' => 1, 'name' => 'Size']
            ],
            'variants' => [
                [
                    'sku' => 'test1',
                    'locationStock' => [
                        [
                            'location' => ['id' => 1, 'name' => 'Ohio', 'enabled' => true],
                            'onHand' => 12
                        ],
                        [
                            'location' => ['id' => 2, 'name' => 'Belvue', 'enabled' => true],
                            'onHand' => 10
                        ]
                    ]
                ],
                [
                    'sku' => 'test2',
                    'locationStock' => [
                        [
                            'location' => ['id' => 1, 'name' => 'Ohio', 'enabled' => true],
                            'onHand' => 1
                        ],
                        [
                            'location' => ['id' => 2, 'name' => 'Belvue', 'enabled' => true],
                            'onHand' => 2
                        ]
                    ]
                ]
            ]
        ];
        $context = new Context();
        $context->setValue(StepExecutionContext::CHANNEL_ID, 1);
        $context->setValue('direction', 'export');

        $actual = $this->mapper->run($data, $context);

        self::assertEquals([
            'sku' => 'test',
            'type' => 'configurable',
            'variations' => [
                ['id' => '11', 'name' => 'VariationSize']
            ],
            'variants' => [
                [
                    'sku' => 'test1',
                    'locationStock' => [
                        [
                            'location' => ['id' => '10', 'name' => 'ChannelOhio'],
                            'onHand' => 12
                        ]
                    ],
                    'onHand' => 22
                ],
                [
                    'sku' => 'test2',
                    'locationStock' => [
                        [
                            'location' => ['id' => '10', 'name' => 'ChannelOhio'],
                            'onHand' => 1
                        ]
                    ],
                    'onHand' => 3
                ]
            ]
        ], $actual);
    }
}