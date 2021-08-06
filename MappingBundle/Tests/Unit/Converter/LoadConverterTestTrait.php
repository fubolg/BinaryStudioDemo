<?php declare(strict_types=1);

namespace BinaryStudioDemo\MappingBundle\Tests\Unit\Converter;

use BinaryStudioDemo\MappingBundle\Converter\Converter;
use BinaryStudioDemo\MappingBundle\Converter\ConverterInterface;
use BinaryStudioDemo\MappingBundle\Factory\Schema;
use BinaryStudioDemo\MappingBundle\Factory\SchemaFactory;
use BinaryStudioDemo\MappingBundle\Provider\DataProviderInterface;
//use BinaryStudioDemo\PlatformBundle\Mapping\Converter\BulkConverterDecorator;
use BinaryStudioDemo\TenantBundle\Component\TenantState;
use BinaryStudioDemo\TenantBundle\Entity\Tenant;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * ToDO: Move to Platform
 * Trait LoadConverterTrait
 * @package BinaryStudioDemo\MappingBundle\Tests\Unit\Converter
 */
trait LoadConverterTestTrait
{
    /**
     * @param $data
     *
     * @return ConverterInterface
     * @throws \Exception
     */
    private function loadMappingConverter($data): ConverterInterface
    {
        $dataProviderMock = self::getMockBuilder(DataProviderInterface::class)->disableOriginalConstructor()->getMock();

        $redisMock = self::getMockBuilder(\Redis::class)->disableOriginalConstructor()->getMock();
        $redisMock->expects(self::any())->method('exists')->willReturn(true);
        $redisMock->expects(self::any())->method('hGet')->will($this->returnCallback(function($redisKey, $itemKey) use ($data) {
            foreach($data as $entry) {
                if(array_key_exists('keys', $entry) && in_array(strtolower($itemKey), array_map(function($k) { return strtolower($k); }, $entry['keys']))) {
                    return json_encode($entry['value'] ?? null);
                }
            }
            return false;
        }));
        $schemaFactory = new SchemaFactory($dataProviderMock, $redisMock);

        return new Converter($schemaFactory);
    }

//    /**
//     * @param $data
//     *
//     * @return ConverterInterface
//     * @throws \Exception
//     */
//    private function loadMappingBulkConverter($data): ConverterInterface
//    {
//        $dataProviderMock = self::getMockBuilder(DataProviderInterface::class)->disableOriginalConstructor()->getMock();
//
//        $redisMock = self::getMockBuilder(\Redis::class)->disableOriginalConstructor()->getMock();
//        $redisMock->expects(self::any())->method('exists')->willReturn(true);
//        $redisMock->expects(self::any())->method('hGet')->will($this->returnCallback(function($redisKey, $itemKey) use ($data) {
//            foreach($data as $entry) {
//                if(array_key_exists('keys', $entry) && in_array(strtolower($itemKey), array_map(function($k) { return strtolower($k); }, $entry['keys']))) {
//                    return json_encode($entry['value'] ?? null);
//                }
//            }
//            return false;
//        }));
//
//        $schemaFactory = new SchemaFactory($dataProviderMock, $redisMock);
//        $converter = new Converter($schemaFactory);
//
//        $schema = self::getMockBuilder(Schema::class)->disableOriginalConstructor()->getMock();
//
//        $schemaFactory2 = self::getMockBuilder(SchemaFactory::class)->disableOriginalConstructor()->getMock();
//        $schemaFactory2->expects(self::any())->method('createNew')->willReturn($schema);
//
//        $schema->expects(self::any())->method('scan')->willReturn(null);
//
//        return new BulkConverterDecorator($converter, $schemaFactory2, self::getMockBuilder(EventDispatcherInterface::class)->getMockForAbstractClass());
//    }

    /**
     * @return Converter
     * @throws \Exception
     */
    private function loadMappingConverterHaveEmptyArray()
    {
        $dataProviderMock = self::getMockBuilder(DataProviderInterface::class)->disableOriginalConstructor()->getMock();

        $redisMock = self::getMockBuilder(\Redis::class)->disableOriginalConstructor()->getMock();
        $redisMock->expects(self::any())->method('exists')->willReturn(true);
        $redisMock->expects(self::any())->method('hGet')->willReturn(false);

        $schemaFactory = new SchemaFactory($dataProviderMock, $redisMock);
        return new Converter($schemaFactory);
    }
}