<?php declare(strict_types=1);

namespace BinaryStudioDemo\MappingBundle\Factory;

use Predis\Client;
use PSRedis\HAClient;
use Predis\Collection\Iterator\HashKey;

/**
 * Class Schema
 * @package BinaryStudioDemo\MappingBundle\Factory
 */
class Schema
{
    /**
     * @var \Redis
     */
    private $redis;

    /**
     * @var string
     */
    private $key;

    /**
     * Schema constructor.
     *
     * @param string $key
     * @param        $redis
     *
     * @throws \Exception
     */
    public function __construct(string $key, $redis)
    {
        if (false === ($redis instanceof Client || $redis instanceof HAClient || $redis instanceof \Redis)) {
            throw new \Exception(get_class($redis), '\Predis\Client or \PSRedis\HAClient or \Redis');
        }

        $this->redis = $redis;
        $this->key = $key;
    }

    /**
     * @param string $key
     *
     * @return array|null
     */
    public function find(string $key): ?array
    {
        $result = $this->redis->hGet($this->key, strtolower($key));
        return $this->decode($result);
    }

    /**
     * @param string $pattern
     *
     * @return array|null
     */
    public function scan(string $pattern): ?array
    {
        $response = [];

        foreach (new HashKey($this->redis, $this->key, $pattern) as $index => $value) {
            $response[$index] = $value;
        }

        if (!empty($response)) {
            ksort($response);

            return array_map(function ($item) {
                return $this->decode($item);
            }, $response);
        }

        return null;
    }

    public function removeAll()
    {
        $this->redis->del($this->key);
    }

    /**
     * @param string $encoded
     *
     * @return array|null
     */
    private function decode($encoded): ?array
    {
        if(null === $encoded) {
            return null;
        }

        # for tests only
        if(is_array($encoded) || is_numeric($encoded)) {

            if(is_numeric($encoded)) {
                return ['id' => (int) $encoded];
            }

            return $encoded;
        }

        # real values should be JSON encoded
        if(null !== $encoded && is_string($encoded)) {
            $decoded = json_decode($encoded, true);

            if (is_numeric($decoded)) {
                return ['id' => (int) $decoded];
            }

            if (is_array($decoded)) {
                return $decoded;
            }
        }

        return null;
    }
}