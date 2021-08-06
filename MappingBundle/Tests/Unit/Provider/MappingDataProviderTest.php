<?php declare(strict_types=1);

namespace BinaryStudioDemo\MappingBundle\Tests\Unit\Provider;

use Enqueue\Util\JSON;
use BinaryStudioDemo\MappingBundle\MappingTypes;
use BinaryStudioDemo\MappingBundle\Provider\DataProviderInterface;
use BinaryStudioDemo\MappingBundle\Provider\MappingDataProvider;
use BinaryStudioDemo\TenantBundle\Component\TenantState;
use BinaryStudioDemo\TenantBundle\Entity\Tenant;
use BinaryStudioDemo\TransportAdapterBundle\Factory\ApiPlatformFactory;
use BinaryStudioDemo\TransportAdapterBundle\Interfaces\TransportInterface;
use BinaryStudioDemo\TransportAdapterBundle\Response\ApiResponse;
use Interop\Queue\Context;
use Interop\Queue\Message;
use Interop\Queue\Producer;
use Interop\Queue\Queue;
use PHPUnit\Framework\TestCase;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Class MappingDataProviderTest
 * @package BinaryStudioDemo\MappingBundle\Tests\Unit\Provider
 */
class MappingDataProviderTest extends TestCase
{
    /**
     * @var MappingDataProvider
     */
    private $mappingDataProvider;
    private $transportFactoryMock;

    private $contextMock;
    private $queueMock;
    private $producerMock;
    private $messageMock;
    private $tenantState;
    private $tenant;
    private $dispatcher;

    public function setUp(): void
    {
        $this->tenant = new Tenant();
        $this->tenant->setId(1);
        $this->tenantState = new TenantState();
        $this->tenantState->setTenant($this->tenant);

        $this->contextMock = self::getMockBuilder(Context::class)->disableOriginalConstructor()->getMock();
        $this->queueMock = self::getMockBuilder(Queue::class)->disableOriginalConstructor()->getMock();
        $this->producerMock = self::getMockBuilder(Producer::class)->disableOriginalConstructor()->getMock();
        $this->messageMock = self::getMockBuilder(Message::class)->disableOriginalConstructor()->getMock();
        $this->dispatcher = self::getMockBuilder(EventDispatcherInterface::class)->getMockForAbstractClass();

        $this->transportFactoryMock = self::getMockBuilder(ApiPlatformFactory::class)->disableOriginalConstructor()->getMock();
        $this->mappingDataProvider = new MappingDataProvider(
            $this->transportFactoryMock,
            $this->tenantState,
            $this->contextMock,
            $this->dispatcher
        );
    }

    /**
     * @param array       $expectedOptions
     * @param string|null $rawBody
     * @param int         $rawCode
     */
    private function buildResponse(array $expectedOptions, string $rawBody = null, $rawCode = 200)
    {
        $response = new ApiResponse();
        $response->setCode($rawCode);

        if (null !== $rawBody) {
            try{
                $array = \GuzzleHttp\json_decode($rawBody, true);
            } catch (\InvalidArgumentException $e) {
                $array = null;
            }
            $response->setBody($array);
        }

        $transportMock = self::getMockBuilder(TransportInterface::class)->disableOriginalConstructor()->getMock();
        $transportMock->expects(self::at(0))->method('withOptions')->with($expectedOptions)->willReturnSelf();
        $transportMock->expects(self::at(1))->method('request')->willReturn($response);

        $this->transportFactoryMock->expects(self::any())->method('createTransport')->willReturn($transportMock);
    }

    /**
     * @covers \BinaryStudioDemo\MappingBundle\Provider\MappingDataProvider::getReplacementsMap
     */
    public function testGetReplacementsMap200(): void
    {
        $channelId = 1;
        $expected = ['map' => [
            [
                'keys' => ['foo', 'bar'],
                'value' => 'baz'
            ],
            [
                'keys' => ['bak'],
                'value' => 'bakbak'
            ]
        ]];

        $this->buildResponse([
            TransportInterface::OPTION_ENDPOINT => '/api/replacements/map/' . $channelId,
            TransportInterface::OPTION_HTTP_METHOD => 'GET',
            TransportInterface::OPTION_BODY => [],
            TransportInterface::OPTION_HEADER => []
        ],
            json_encode($expected),
            200);

        $response = $this->mappingDataProvider->getReplacementsMap($channelId);
        self::assertCount(2, $response);
        self::assertEquals($expected['map'], $response);
    }

    /**
     * @covers \BinaryStudioDemo\MappingBundle\Provider\MappingDataProvider::getReplacementsMap
     */
    public function testGetReplacementsMapEmpty(): void
    {
        $channelId = 1;
        $expected = ['map' => []];

        $this->buildResponse([
            TransportInterface::OPTION_ENDPOINT => '/api/replacements/map/' . $channelId,
            TransportInterface::OPTION_HTTP_METHOD => 'GET',
            TransportInterface::OPTION_BODY => [],
            TransportInterface::OPTION_HEADER => []
        ],
            json_encode($expected),
            200);

        $response = $this->mappingDataProvider->getReplacementsMap($channelId);
        self::assertCount(0, $response);
        self::assertEquals([], $response);
    }

    /**
     * @covers \BinaryStudioDemo\MappingBundle\Provider\MappingDataProvider::unlinkReplacements
     */
    public function testUnlinkReplacements201(): void
    {
        $expected = file_get_contents(__DIR__ . '/unlinkResponse.json');
        $filter = [
            'referenceTo' => 1, // unlink Entity ID
            'type'        => MappingTypes::TYPE_ATTRIBUTE,
        ];

        $this->buildResponse([
            TransportInterface::OPTION_ENDPOINT => '/api/replacements/unlink',
            TransportInterface::OPTION_HTTP_METHOD => 'POST',
            TransportInterface::OPTION_BODY => $filter,
            TransportInterface::OPTION_HEADER => []
        ],
            $expected,
            201);

        $response = $this->mappingDataProvider->unlinkReplacements($filter);
        self::assertEquals(json_decode($expected, true), $response);
    }

    /**
     * @covers \BinaryStudioDemo\MappingBundle\Provider\MappingDataProvider::unlinkReplacements
     */
    public function testUnlinkReplacements400(): void
    {
        $filter = [
            'referenceTo' => 1, // unlink Entity ID
            'type'        => MappingTypes::TYPE_ATTRIBUTE,
        ];

        $this->buildResponse([
            TransportInterface::OPTION_ENDPOINT => '/api/replacements/unlink',
            TransportInterface::OPTION_HTTP_METHOD => 'POST',
            TransportInterface::OPTION_BODY => $filter,
            TransportInterface::OPTION_HEADER => []
        ],
            json_encode([
                '@type' => 'hydra:Error',
                '@hydra:title' => 'An error occurred',
                '@hydra:description' => 'Something went wrong',
            ]),
            400);

        $response = $this->mappingDataProvider->unlinkReplacements($filter);
        self::assertEquals([
            'ErrorCode' => '400',
            'Message' => 'Something went wrong'
        ], $response);
    }

    /**
     * @covers \BinaryStudioDemo\MappingBundle\Provider\MappingDataProvider::unlinkReplacements
     */
    public function testUnlinkReplacements500(): void
    {
        $filter = [
            'referenceTo' => 1, // unlink Entity ID
            'type'        => MappingTypes::TYPE_ATTRIBUTE,
        ];

        $this->buildResponse([
            TransportInterface::OPTION_ENDPOINT => '/api/replacements/unlink',
            TransportInterface::OPTION_HTTP_METHOD => 'POST',
            TransportInterface::OPTION_BODY => $filter,
            TransportInterface::OPTION_HEADER => []
        ],
            'Service has gone',
            400);

        $response = $this->mappingDataProvider->unlinkReplacements($filter);
        self::assertEquals([
            'ErrorCode' => '500',
            'Message' => null
        ], $response);
    }

    /**
     * @covers \BinaryStudioDemo\MappingBundle\Provider\MappingDataProvider::getReplacements
     */
    public function testGetReplacements200(): void
    {
        $expected = file_get_contents(__DIR__ . '/getReplacementsResponse.json');
        $filter = [
            'referenceTo[exists]' => 1,
            'channel'             => 1,
            'page' => 1,
            'perPage' => 500// channel ID
        ];

        $this->buildResponse([
            TransportInterface::OPTION_ENDPOINT => '/api/replacements',
            TransportInterface::OPTION_HTTP_METHOD => 'GET',
            TransportInterface::OPTION_BODY => $filter,
            TransportInterface::OPTION_HEADER => []
        ],
            $expected,
            200);

        $response = $this->mappingDataProvider->getReplacements($filter);
        self::assertEquals(json_decode($expected, true), $response);
    }

    /**
     * @covers \BinaryStudioDemo\MappingBundle\Provider\MappingDataProvider::getReplacements
     */
    public function testGetReplacements400(): void
    {
        $filter = [
            'referenceTo[exists]' => 1,
            'channel'             => 1,
            'page' => 1,
            'perPage' => 500// channel ID
        ];

        $this->buildResponse([
            TransportInterface::OPTION_ENDPOINT => '/api/replacements',
            TransportInterface::OPTION_HTTP_METHOD => 'GET',
            TransportInterface::OPTION_BODY => $filter,
            TransportInterface::OPTION_HEADER => []
        ],
            json_encode([
                '@type' => 'hydra:Error',
                '@hydra:title' => 'An error occurred',
                '@hydra:description' => 'Invalid argument exception',
            ]),
            400);

        $response = $this->mappingDataProvider->getReplacements($filter);
        self::assertEquals([
            'ErrorCode' => '400',
            'Message' => 'Invalid argument exception'
        ], $response);
    }

    public function testSend()
    {
        $requestData = $data = [
            'channel' => 1,
            'carriers' => [
                [
                    'id' => '1',
                    'name' => 'Label',
                    'label' => 'Label',
                    'CarrierID' => '1',
                    'CarrierName' => 'Label',
                    'CarrierCode' => '123123'
                ]
            ]
        ];

        $requestData['tenant'] = 1;

        $this->contextMock->expects(self::once())->method('createQueue')->with(DataProviderInterface::DEFAULT_QUEUE_SYNC)->will($this->returnValue($this->queueMock));
        $this->contextMock->expects(self::once())->method('createProducer')->will($this->returnValue($this->producerMock));
        $this->contextMock->expects(self::once())->method('createMessage')->with(JSON::encode($requestData))->will($this->returnValue($this->messageMock));
        $this->producerMock->expects(self::once())->method('send')->with($this->queueMock, $this->messageMock);

        $this->mappingDataProvider->send(DataProviderInterface::DEFAULT_QUEUE_SYNC, $data);
    }
}