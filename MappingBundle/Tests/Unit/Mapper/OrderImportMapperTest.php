<?php declare(strict_types=1);

namespace BinaryStudioDemo\MappingBundle\Tests\Unit\Mapper;

use BinaryStudioDemo\CoreBundle\Context\Context;
use BinaryStudioDemo\ImportExportBundle\Context\StepExecutionContext;
use BinaryStudioDemo\MappingBundle\Converter\ConverterInterface;
use BinaryStudioDemo\MappingBundle\Mapper\OrderImportMapper;
use BinaryStudioDemo\MappingBundle\Mapper\ProductImportMapper;
use BinaryStudioDemo\MappingBundle\Provider\MappingDataProvider;
use BinaryStudioDemo\PlatformBundle\Mapping\Automapping\AutomappingBuilderInterface;
use PHPUnit\Framework\TestCase;

/**
 * Class OrderImportMapperTest
 * @package BinaryStudioDemo\MappingBundle\Tests\Unit\Mapper
 */
class OrderImportMapperTest extends TestCase
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

        $this->mapper = new OrderImportMapper($this->converter, $this->automapping, $this->mappingProvider);
    }

    public function test_should_support_import_direction()
    {
        $data = [
            "number" => "17999078",
            "shippingMethod" => "Amazon Merchants@Standard",
            "shippingAddress" => [],
            "billingAddress" => [],
            "items" => [],
            "shipments" => [
                [
                    "number" => "10471463",
                    "carrier" => 'Amazon Merchants@Standard',
                    "location" => "123"
                ]
            ]
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
            "number" => "17999078",
            "shippingAddress" => [],
            "billingAddress" => [],
            "items" => [],
            "shipments" => [
                [
                    "number" => "10471463",
                    "carrier" => 'Amazon Merchants@Standard',
                    "location" => "123"
                ]
            ]
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
            'number' => 'test'
        ];
        $context = new Context();
        $context->setValue(StepExecutionContext::CHANNEL_ID, 1);
        $context->setValue('direction', 'import');

        $actual = $this->mapper->run($data, $context);

        self::assertEquals($data, $actual);
    }

    public function test_should_change_carrier_and_location_of_childs()
    {
        $this->mappingProvider->expects(self::never())->method('syncReplacements');

        $this->converter->expects(self::at(0))->method('setChannelId')->with(1);
        $this->converter->expects(self::at(1))->method('convert')->with(['channel' => 1, 'label' => 'Amazon Merchants@Standard', 'type' => 'carrier', 'data' => ['name' => 'Amazon Merchants@Standard']])->willReturn(['id' => 1]);
        $this->converter->expects(self::at(2))->method('convert')->with(['channel' => 1, 'label' => 123, 'type' => 'location', 'data' => ['id' => 123]])->willReturn(['id' => 2]);

        $data = [
            "number" => "17999078",
            "requestedCarrier" => "Amazon Merchants@Standard",
            "shippingAddress" => [],
            "billingAddress" => [],
            "items" => [],
            "shipments" => [
                [
                    "number" => "10471463",
                    "carrier" => 'Amazon Merchants@Standard',
                    "location" => 123
                ]
            ]
        ];

        $context = new Context();
        $context->setValue(StepExecutionContext::CHANNEL_ID, 1);
        $context->setValue('direction', 'import');

        $actual = $this->mapper->run($data, $context);

        self::assertEquals([
            "number" => "17999078",
            "requestedCarrier" => ['id' => 1],
            "shippingAddress" => [],
            "billingAddress" => [],
            "items" => [],
            "shipments" => [
                [
                    "number" => "10471463",
                    "carrier" => ['id' => 1],
                    "location" => ['id' => 2]
                ]
            ]
        ], $actual);
    }

    public function test_carriers_should_not_use_automap()
    {
        $this->converter->expects(self::at(0))->method('setChannelId')->with(1);
        $this->automapping->expects(self::never())->method('build');

        $data = [
            "number" => "17999078",
            "requestedCarrier" => "Amazon Merchants@Standard",
            "shippingAddress" => [],
            "billingAddress" => [],
            "items" => [],
            "shipments" => [
                [
                    "number" => "10471463",
                    "carrier" => 'Amazon Merchants@Standard'
                ]
            ]
        ];

        $context = new Context();
        $context->setValue(StepExecutionContext::CHANNEL_ID, 1);
        $context->setValue('direction', 'import');

        $actual = $this->mapper->run($data, $context);

        self::assertEquals([
            "number" => "17999078",
            "requestedCarrier" => null,
            "shippingAddress" => [],
            "billingAddress" => [],
            "items" => [],
            "shipments" => [
                [
                    "number" => "10471463",
                    "carrier" => null
                ]
            ]
        ], $actual);
    }
}