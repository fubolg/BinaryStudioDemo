<?php declare(strict_types=1);

namespace BinaryStudioDemo\MappingBundle\Tests\Unit\Factory;

use BinaryStudioDemo\MappingBundle\Factory\Schema;
use PHPUnit\Framework\TestCase;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * Class SchemaTest
 * @package BinaryStudioDemo\MappingBundle\Tests\Unit\Factory
 */
class SchemaTest extends WebTestCase
{
    private $key = 'bar';

    /**
     * @var \Redis
     */
    private $redis;

    private $data = [];

    /**
     * @var Schema
     */
    private $schema;

    public function setUp(): void
    {
        self::bootKernel();

        $this->redis = static::getContainer()
            ->get('snc_redis.default');
        $this->redis->del($this->key);
        $this->schema = new Schema($this->key, $this->redis);
    }

    public function testFindInEmptySchema()
    {
        self::assertEquals(null, $this->schema->find('ddd'));
    }

    public function testFoundArray()
    {
        $this->loadFindData();
        self::assertEquals(['id' => 2], $this->schema->find('cd'));
    }

    public function testFoundInteger()
    {
        $this->loadFindData();
        self::assertEquals(['id' => 1], $this->schema->find('ab'));
        self::assertEquals([], $this->schema->find('ef'));

        # Bool is not supported here!
        self::assertEquals(null, $this->schema->find('gh'));
    }

    public function testScan()
    {
        $this->loadScanData();

        self::assertEquals([
            '1.1.attribute.1.i' => ['id' => 1],
            '1.1.attribute.size.i' => ['id' => 1],
            '1.1.attribute.2.i' => ['id' => 2],
            '1.1.attribute.color.i' => ['id' => 2],
            '1.1.attribute.3.i' => ['id' => 3],
            '1.1.attribute.material.i' => ['id' => 3],
        ], $this->schema->scan('1.1.attribute.*.i'));

        self::assertEquals([
            '1.1.classification.1.i' => ['id' => 1]
        ], $this->schema->scan('1.1.classification.*.i'));


        self::assertEquals([
            '1.1.classification.1.i' => ['id' => 1],
            '1.1.classification.1.e' => ['id' => 1, 'name' => 'classification'],
        ], $this->schema->scan('1.1.classification.*'));
    }

    /**
     * @return array
     */
    private function loadFindData()
    {
        $values = [
            'ab' => json_encode(1),
            'cd' => json_encode(['id' => 2]),
            'ef' => json_encode(new \stdClass()),
            'gh' => json_encode(true),
        ];

        foreach($values as $key => $value) {
            $this->redis->hset($this->key, $key, $value);
        }
    }

    /**
     * @return array
     */
    private function loadScanData()
    {
        $values = [
            '1.1.attribute.1.i' => json_encode(1),
            '1.1.attribute.size.i' => json_encode(1),
            '1.1.attribute.1.e' => json_encode(['id' => '1', 'name' => 'test']),
            '1.1.attribute.2.i' => json_encode(2),
            '1.1.attribute.color.i' => json_encode(2),
            '1.1.attribute.2.e' => json_encode(['id' => '2', 'name' => 'test']),
            '1.1.attribute.3.i' => json_encode(3),
            '1.1.attribute.material.i' => json_encode(3),
            '1.1.attribute.3.e' => json_encode(['id' => '3', 'name' => 'test']),
            '1.2.attribute.1.i' => json_encode(1),
            '1.2.attribute.Size.i' => json_encode(1),
            '1.2.attribute.2.i' => json_encode(2),
            '1.2.attribute.Color.i' => json_encode(2),
            '1.2.attribute.3.i' => json_encode(3),
            '1.2.attribute.Material.i' => json_encode(3),
            '1.1.classification.1.i' => json_encode(1),
            '1.1.classification.1.e' => json_encode(['id' => 1, 'name' => 'classification']),
            '1.1.location.1.i' => json_encode(1),
            'cd' => json_encode(['id' => 2]),
            'ef' => json_encode(new \stdClass()),
            'gh' => json_encode(true),
        ];

        foreach($values as $key => $value) {
            $this->redis->hset($this->key, $key, $value);
        }
    }
}
