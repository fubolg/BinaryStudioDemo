<?php declare(strict_types=1);

namespace BinaryStudioDemo\MappingBundle\Provider;

use Enqueue\Util\JSON;
use BinaryStudioDemo\CoreBundle\Interfaces\LoggerAwareInterface;
use BinaryStudioDemo\CoreBundle\Traits\LoggerAwareTrait;
use BinaryStudioDemo\MappingBundle\Event\MappingRequestSuccessEvent;
use BinaryStudioDemo\TenantBundle\Component\TenantState;
use BinaryStudioDemo\TransportAdapterBundle\Factory\ApiPlatformFactory;
use BinaryStudioDemo\TransportAdapterBundle\Interfaces\ResponseInterface;
use BinaryStudioDemo\TransportAdapterBundle\Interfaces\TransportInterface;
use Interop\Queue\Context;
use Psr\Log\LogLevel;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Class MappingDataProvider
 * @package BinaryStudioDemo\MappingBundle\Provider
 */
class MappingDataProvider implements DataProviderInterface, LoggerAwareInterface
{
    use LoggerAwareTrait;

    private ApiPlatformFactory $transportFactory;
    private TenantState $tenantState;
    private Context $context;
    private EventDispatcherInterface $dispatcher;
    private array $msTransports = [];

    /**
     * MappingDataProvider constructor.
     *
     * @param ApiPlatformFactory       $transportFactory
     * @param TenantState              $tenantState
     * @param Context                  $context
     * @param EventDispatcherInterface $dispatcher
     */
    public function __construct(
        ApiPlatformFactory $transportFactory,
        TenantState $tenantState,
        Context $context,
        EventDispatcherInterface $dispatcher
    ) {
        $this->transportFactory = $transportFactory;
        $this->tenantState = $tenantState;
        $this->context = $context;
        $this->dispatcher = $dispatcher;
    }

    /**
     * @param string $method
     * @param string $msName
     * @param string $endpoint
     * @param array  $filter
     * @param array  $options
     *
     * @return ResponseInterface|null
     */
    protected function request(string $method, string $msName, string $endpoint, array $filter = [], array $options = []): ?ResponseInterface
    {
        $defaultOptions = [
            TransportInterface::OPTION_ENDPOINT => $endpoint,
            TransportInterface::OPTION_HTTP_METHOD => $method,
            TransportInterface::OPTION_BODY => $filter,
            TransportInterface::OPTION_HEADER => []
        ];

        $requestOptions = array_merge($defaultOptions, $options);

        $transport = $this->getMsTransport($msName);
        $response = $transport->withOptions($requestOptions)->request();

        if ($response instanceof ResponseInterface) {
            $body = $response->getBody();

            if ($response->getCode() >= 500) {
                $this->log(LogLevel::ERROR, 'Links microservice fails', ['code' => $response->getCode(), 'body' => $body, 'message' => $response->getMessage()]);
            }

            return $response;
        }

        $this->log(LogLevel::CRITICAL, 'Links microservice returns NULL', ['response' => $response]);
        return null;
    }

    /**
     * @param             $response
     * @param string      $endpoint
     * @param string|null $attributeName
     * @param bool        $returnFullBody
     *
     * @return array|null
     */
    private function responseToArray($response, string $endpoint, string $attributeName = null, bool $returnFullBody = false): ?array
    {
        if ($response instanceof ResponseInterface) {
            $body = $response->getBody();

            if ($response->getCode() >= 200 &&
                $response->getCode() < 300) {

                # 204 response should have empty Body
                if ($response->getCode() === 204 && !is_array($body)) {
                    return null;
                }

                if (null === $attributeName) {
                    if (array_key_exists('@type', $body) && $body['@type'] === 'hydra:Error') {
                        return [
                            'ErrorCode' => '400',
                            'Message' => $body['@hydra:description'] ?? $response->getMessage() ?? 'Mapping no error description provided.'
                        ];
                    }
                }

                if (is_array($body) && null !== $attributeName && !array_key_exists($attributeName, $body)) {
                    $this->log(LogLevel::ERROR, sprintf('Wrong data came from %s endpoint, expected and array with %s attribute', $endpoint, $attributeName), ['body' => $body]);

                    if (array_key_exists('@type', $body) && $body['@type'] === 'hydra:Error') {
                        return [
                            'ErrorCode' => '400',
                            'Message' => $body['@hydra:description'] ?? $response->getMessage() ?? 'Mapping no error description provided.'
                        ];
                    }

                    return $body;
                }

                return $returnFullBody === true
                    ? $body
                    : $body[$attributeName];
            }

            if ($response->getCode() >= 400) {
                if (is_array($body) && array_key_exists('@type', $body) && $body['@type'] === 'hydra:Error') {
                    return [
                        'ErrorCode' => (string)$response->getCode(),
                        'Message' => $body['@hydra:description'] ?? $response->getMessage() ?? 'Mapping no error description provided'
                    ];
                }

                return [
                    'ErrorCode' => '500',
                    'Message' => $response->getMessage()
                ];
            }
        }

        return null;
    }

    /**
     * @param int $channelId
     *
     * @return array|null
     */
    public function getReplacementsMap(int $channelId): ?array
    {
        $response = $this->request('GET', self::MS_MAPPING_NAME, '/api/replacements/map/' . $channelId);
        $result = $this->responseToArray($response, 'GET /api/replacements/map/' . $channelId, 'map');

        if($response->getCode() >= 200 && $response->getCode() < 300) {
            $this->dispatcher->dispatch(new MappingRequestSuccessEvent($result, $channelId), MappingRequestSuccessEvent::NAME);
        }

        return $result;
    }

    /**
     * @param array $filter
     *
     * @return array|null
     */
    public function unlinkReplacements(array $filter): ?array
    {
        $response = $this->request('POST', self::MS_MAPPING_NAME, '/api/replacements/unlink', $filter);
        return $this->responseToArray($response, 'POST /api/replacements/unlink', null, true);
    }

    /**
     * @param array $filter
     *
     * @return array|null
     */
    public function getReplacements(array $filter = []): ?array
    {
        $response = $this->request('GET', self::MS_MAPPING_NAME, '/api/replacements', $filter);
        return $this->responseToArray($response, 'GET /api/replacements', 'hydra:member', true);
    }

    /**
     * @param array $data
     * @param int   $channelId
     *
     * @return array|null
     */
    public function syncReplacements(array $data, int $channelId): ?array
    {
        if(empty($data)) {
            return [];
        }

        $data['channel'] = $channelId;

        $response = $this->request('POST', self::MS_MAPPING_NAME, '/api/replacements/sync', $data);
        $result = $this->responseToArray($response, 'POST /api/replacements/sync', 'map');

        if($response->getCode() >= 200 && $response->getCode() < 300) {
            $event = new MappingRequestSuccessEvent($result, $channelId);
            $this->dispatcher->dispatch($event);
            unset($event);
        }

        return $result;
    }

    /**
     * @param string $msName
     *
     * @return TransportInterface|mixed
     */
    private function getMsTransport(string $msName): TransportInterface
    {
        if (array_key_exists($msName, $this->msTransports)) {
            return $this->msTransports[$msName];
        }

        $newTransport = $this->transportFactory->createTransport($msName, 0);
        $this->msTransports[$msName] = $newTransport;

        return $newTransport;
    }

    /**
     * @param string $queue
     * @param array  $data
     *
     * @return mixed|void
     * @throws \Interop\Queue\Exception
     */
    public function send(string $queue, array $data): void
    {
        try {
            $tenant = $this->tenantState->getTenant();
            $data['tenant'] = $tenant ? $tenant->getId() : 'guest';

            $queue = $this->context->createQueue($queue);
            $producer = $this->context->createProducer();
            $message = $this->context->createMessage(JSON::encode($data));
            $producer->send($queue, $message);

            unset($queue);
            unset($producer);
            unset($message);

        } catch (\Exception $e) {
            $this->log(LogLevel::CRITICAL,
                'Mapping: MQ send failed: ' . $e->getMessage(),
                [
                    'data' => $data,
                    'exception' => $e
                ]
            );
        }
    }
}