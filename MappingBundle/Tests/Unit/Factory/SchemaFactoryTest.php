<?php declare(strict_types=1);

namespace BinaryStudioDemo\MappingBundle\Tests\Unit\Factory;

use BinaryStudioDemo\MappingBundle\Factory\Schema;
use BinaryStudioDemo\MappingBundle\Factory\SchemaFactory;
use BinaryStudioDemo\MappingBundle\Provider\DataProviderInterface;
use PHPUnit\Framework\TestCase;

/**
 * Class SchemaFactoryTest
 * @package BinaryStudioDemo\MappingBundle\Tests\Unit\Factory
 */
class SchemaFactoryTest extends TestCase
{
    /**
     * @var \PHPUnit_Framework_MockObject_MockObject | DataProviderInterface
     */
    private $dataProviderMock;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject | SchemaFactory
     */
    private $schemaFactory;

    private $data = [];

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject | \Redis
     */
    private $redisMock;

    /**
     * @var
     */
    private $channelId;

    /**
     * @inheritdoc
     */
    public function setUp(): void
    {
        parent::setUp(); // TODO: Change the autogenerated stub

        $this->channelId = 1;
        $this->dataProviderMock = self::getMockBuilder(DataProviderInterface::class)->getMockForAbstractClass();

        $this->redisMock = self::getMockBuilder(\Redis::class)->disableOriginalConstructor()->getMock();
        $this->redisMock->expects(self::any())->method('hGet')->will($this->returnCallback(function ($redisKey, $itemKey) {
            foreach ($this->data as $entry) {
                if (array_key_exists('keys', $entry) && in_array(strtolower($itemKey), array_map(function ($k) {
                        return strtolower($k);
                    }, $entry['keys']))) {
                    return $entry['value'] ?? null;
                }
            }
            return false;
        }));

        $this->schemaFactory = new SchemaFactory($this->dataProviderMock, $this->redisMock);
    }

    public function testThrowsExceptionWithoutChannel(): void
    {
        self::expectException(\LogicException::class);

        $this->schemaFactory
            ->createNew();
    }

    public function testLoadEmptyDataFromDataProvider(): void
    {
        $this->redisMock->expects(self::once())->method('exists')->willReturn(false);
        $this->redisMock->expects(self::once())->method('hset');
        $this->dataProviderMock
            ->expects(self::once())
            ->method('getReplacementsMap')
            ->willReturn([]);

        // action
        $schema = $this->schemaFactory
            ->setChannelId((int) $this->channelId)
            ->createNew();

        self::assertInstanceOf(Schema::class, $schema);
        $reflection = new \ReflectionClass($schema);
        $property = $reflection->getProperty('key');
        $property->setAccessible(true);
        self::assertEquals('replacements.1', $property->getValue($schema));
    }

    public function testGetReplacementsMapFromDataProvider(): void
    {
        $this->dataProviderMock
            ->expects(self::once())
            ->method('getReplacementsMap')
            ->willReturn([
                    [
                        'keys' => [
                            'foo',
                            'baz',
                        ],
                        'value' => 'bar'
                    ],
                    [
                        'keys' => [
                            'bak'
                        ],
                        'value' => [
                            'id' => '4',
                            'name' => 'bakbak'
                        ]
                    ]
                ]
            );

        $this->redisMock->expects(self::at(0))->method('exists')->willReturn(false);
        $this->redisMock->expects(self::at(1))->method('del')->willReturn(true);
        $this->redisMock->expects(self::at(2))->method('hset')->with('replacements.1', 'foo', json_encode('bar'))->willReturn(true);
        $this->redisMock->expects(self::at(3))->method('hset')->with('replacements.1', 'baz', json_encode('bar'))->willReturn(true);
        $this->redisMock->expects(self::at(4))->method('hset')->with('replacements.1', 'bak', json_encode([ 'id' => '4', 'name' => 'bakbak']))->willReturn(true);
        $this->redisMock->expects(self::at(5))->method('expire')->with('replacements.1', 3600)->willReturn(true);
        $this->redisMock->expects(self::at(6))->method('exists')->willReturn(true);

        // action
        $schema = $this->schemaFactory
            ->setChannelId((int) $this->channelId)
            ->createNew();

        self::assertInstanceOf(Schema::class, $schema);
        $reflection = new \ReflectionClass($schema);
        $property = $reflection->getProperty('key');
        $property->setAccessible(true);

        self::assertEquals('replacements.1', $property->getValue($schema));

        $schema2 = $this->schemaFactory
            ->setChannelId((int) $this->channelId)
            ->createNew();

        self::assertSame($schema, $schema2);
    }
}