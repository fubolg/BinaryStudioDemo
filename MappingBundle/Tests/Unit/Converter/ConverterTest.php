<?php declare(strict_types=1);

namespace BinaryStudioDemo\MappingBundle\Tests\Unit\Converter;

use BinaryStudioDemo\MappingBundle\Converter\Converter;
use BinaryStudioDemo\MappingBundle\Converter\ConverterInterface;
use BinaryStudioDemo\MappingBundle\Factory\Schema;
use BinaryStudioDemo\MappingBundle\Factory\SchemaFactory;
use BinaryStudioDemo\MappingBundle\Factory\SchemaFactoryInterface;
use PHPUnit\Framework\TestCase;

/**
 * Class ConverterTest
 * @package BinaryStudioDemo\MappingBundle\Tests\Unit\Converter
 */
class ConverterTest extends TestCase
{
    /**
     * @var SchemaFactoryInterface | \PHPUnit_Framework_MockObject_MockObject
     */
    private $schemaFactory;

    /**
     * @var ConverterInterface
     */
    private $converter;

    public function setUp(): void
    {
        $this->schemaFactory = $this->createMock(SchemaFactory::class);

        $this->converter = new Converter(
            $this->schemaFactory
        );
    }

    public function test_shouldnt_convert_when_subject_is_null()
    {
        self::assertNull($this->converter->convert(null));
    }

    public function test_shouldnt_convert_when_channel_is_not_defined()
    {
        $this->expectException(\LogicException::class);
        $this->converter->convert($this->getDataArray());
    }

    public function test_shouldnt_convert_when_type_is_not_defined()
    {
        self::expectException(\InvalidArgumentException::class);
        self::expectExceptionMessage('Conversion subject must contain "type" attribute');

        $this->converter->setChannelId(1);
        $data = $this->getDataArray();
        unset($data['type']);
        $this->converter->convert($data);
    }

    public function test_should_convert_by_name()
    {
        $subject = $this->getDataArray();

        $this->schemaFactory->expects(self::once())
            ->method('setChannelId')
            ->with(1)
            ->willReturn($this->schemaFactory);

        $schema = self::getMockBuilder(Schema::class)->disableOriginalConstructor()->getMock();
        $schema->expects(self::once())->method('find')->with('1.attribute.size.i')->willReturn(['ok']);

        $this->schemaFactory->expects(self::once())
            ->method('createNew')
            ->willReturn($schema);

        $this->converter->setChannelId(1);

        self::assertEquals(['ok'], $this->converter->convert($subject));
    }

    public function test_should_convert_by_id()
    {
        $subject = $this->getDataArray();
        $subject['data']['id'] = '11';

        $this->schemaFactory->expects(self::once())
            ->method('setChannelId')
            ->with(1)
            ->willReturn($this->schemaFactory);

        $schema = self::getMockBuilder(Schema::class)->disableOriginalConstructor()->getMock();
        $schema->expects(self::at(0))->method('find')->with('1.attribute.size.i')->willReturn(null);
        $schema->expects(self::at(1))->method('find')->with('1.attribute.11.i')->willReturn(['ok']);

        $this->schemaFactory->expects(self::once())
            ->method('createNew')
            ->willReturn($schema);

        $this->converter->setChannelId(1);

        self::assertEquals(['ok'], $this->converter->convert($subject));
    }

    /**
     * @return array
     */
    private function getDataArray()
    {
        return [
            'type' => 'attribute',
            'label' => 'Size',
            'data' => [
                'name' => 'Size',
                'value' => 'XXL',
                'id' => null,
            ]
        ];
    }
}
