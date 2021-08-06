<?php declare(strict_types=1);

namespace BinaryStudioDemo\MappingBundle\Factory;

use BinaryStudioDemo\CoreBundle\Interfaces\LoggerAwareInterface;
use BinaryStudioDemo\CoreBundle\Traits\LoggerAwareTrait;
use BinaryStudioDemo\MappingBundle\Provider\DataProviderInterface;
use Predis\Client;
use PSRedis\HAClient;

/**
 * Class SchemaFactory
 * @package BinaryStudioDemo\MappingBundle\Factory
 */
class SchemaFactory implements SchemaFactoryInterface, LoggerAwareInterface
{
    use LoggerAwareTrait;

    private DataProviderInterface $dataProvider;
    private ?int $channelId = null;
    private array $cachedSchemas = [];
    private int $ttl;

    /**
     * @var \Redis
     */
    private $redis;


    /**
     * SchemaFactory constructor.
     *
     * @param DataProviderInterface $dataProvider
     * @param                       $redis
     * @param int                   $ttl
     *
     * @throws \Exception
     */
    public function __construct(DataProviderInterface $dataProvider, $redis, int $ttl = 3600)
    {
        $this->dataProvider = $dataProvider;

        if (false === ($redis instanceof Client || $redis instanceof HAClient || $redis instanceof \Redis)) {
            throw new \Exception(get_class($redis), '\Predis\Client or \PSRedis\HAClient or \Redis');
        }

        $this->redis = $redis;
        $this->ttl = $ttl;
    }

    /**
     * {@inheritdoc}
     */
    public function setChannelId(int $channelId): SchemaFactoryInterface
    {
        $this->channelId = $channelId;
        return $this;
    }

    /**
     * @param array $item
     */
    public function enhanceWithData(array $item): void
    {
        $output = [];

        if (array_key_exists('referenceTo', $item)
            && null !== $item['referenceTo']
            && array_key_exists('data', $item)
            && array_key_exists('type', $item)
        ) {

            $import['value'] = $item['referenceTo'];

            if (array_key_exists('name', $item['data']) && null !== $item['data']['name']) {
                $import['keys'][] = sprintf('%s.%s.%s.i', $this->channelId, $item['type'], $item['data']['name']);
            }

            if (array_key_exists('id', $item['data']) && null !== $item['data']['id']) {
                $import['keys'][] = sprintf('%s.%s.%s.i', $this->channelId, $item['type'], $item['data']['id']);
            }

            if (array_key_exists('keys', $import) && !empty($import['keys'])) {
                $output[] = $import;
            }

            $output[] = [
                'keys' => [
                    sprintf('%s.%s.%s.e', $this->channelId, $item['type'], $item['referenceTo'])
                ],
                'value' => $item['data']
            ];

        }

        if (!empty($output)) {
            $this->setKeys($output, false);
        }
    }

    /**
     * @param array $keys
     */
    public function decreaseKeys(array $keys): void
    {
        $key = $this->getKey();
        $keys = array_map(function ($elem) {
            return strtolower($elem);
        }, $keys);
        $this->redis->hDel($key, ...$keys);
    }

    /**
     * @return Schema
     * @throws \Exception
     */
    public function createNew(): Schema
    {
        if (null === $this->channelId) {
            throw new \LogicException('ChannelId must be set before conversion');
        }

        $key = $this->getKey();

        if (!array_key_exists($key, $this->cachedSchemas)) {
            $this->cachedSchemas[$key] = $this->getInstance();
        }

        $key = $this->getKey();

        if (!$this->redis->exists($key)) {
            $map = $this->dataProvider->getReplacementsMap((int)$this->channelId);
            $this->setKeys($map, true);
        }

        return $this->cachedSchemas[$key];
    }

    /**
     * @return string
     */
    protected function getKey(): string
    {
        return 'replacements.' . $this->channelId;
    }

    /**
     * @return Schema
     * @throws \Exception
     */
    protected function getInstance(): Schema
    {
        return new Schema($this->getKey(), $this->redis);
    }

    /**
     * @param array $data
     * @param bool  $fullErase
     */
    public function setKeys(array $data, bool $fullErase = false): void
    {
        $key = $this->getKey();
        $income = [];

        foreach ($data as $item) {
            foreach ($item['keys'] ?? [] as $k) {
                if (null !== ($income[$k] ?? null)) {
                    continue;
                }
                $income[$k] = json_encode($item['value']);
            }
        }

        if ($fullErase === true) {
            $this->redis->del($key);
        }

        if (empty($income)) {
            $this->redis->hSet($key, 'empty', '');
        }

        foreach ($income as $k => $value) {
            $this->redis->hSet($key, strtolower($k), $value);
        }

        $this->redis->expire($key, $this->ttl);
    }

}