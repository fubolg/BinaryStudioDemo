<?php declare(strict_types=1);

namespace BinaryStudioDemo\MappingBundle\Tests\Unit\Mapper;

use BinaryStudioDemo\CoreBundle\Interfaces\ContextInterface;
use BinaryStudioDemo\MappingBundle\Interfaces\MapperInterface;
use BinaryStudioDemo\MappingBundle\Mapper\ChainMapper;
use PHPUnit\Framework\TestCase;

/**
 * Class ChainMapperTest
 * @package BinaryStudioDemo\MappingBundle\Tests\Unit\Mapper
 */
class ChainMapperTest extends TestCase {

    public function test_should_run_mapper()
    {
        $chain = new ChainMapper([
            new class implements MapperInterface {
                public function supports(array $d, ContextInterface $context): bool {return false;}
                public function run(array $d, ContextInterface $context): ?array {return ['one'];}
                public function getErrorMessage(): ?string { return 'one error';}
                public function resetErrorMessage(): void {}
            },
            new class implements MapperInterface {
                public function supports(array $d, ContextInterface $context): bool {return true;}
                public function run(array $d, ContextInterface $context): ?array {return ['two'];}
                public function getErrorMessage(): ?string { return 'two error';}
                public function resetErrorMessage(): void {}
            },
            new class implements MapperInterface {
                public function supports(array $d, ContextInterface $context): bool {return true;}
                public function run(array $d, ContextInterface $context): ?array {return ['three'];}
                public function getErrorMessage(): ?string { return 'three error';}
                public function resetErrorMessage(): void {}
            }
        ]);

        $result = $chain->run([], self::getMockBuilder(ContextInterface::class)->getMockForAbstractClass());
        $errors = $chain->getErrorMessage();

        self::assertEquals(['two'], $result);
        self::assertEquals('two error', $errors);
    }

    public function test_should_return_subject_when_no_mappers_found()
    {
        $chain = new ChainMapper([
            new class implements MapperInterface {
                public function supports(array $d, ContextInterface $context): bool {return false;}
                public function run(array $d, ContextInterface $context): ?array {return ['one'];}
                public function getErrorMessage(): ?string { return null; }
                public function resetErrorMessage(): void {}
            }
        ]);

        $result = $chain->run(['test'], self::getMockBuilder(ContextInterface::class)->getMockForAbstractClass());
        $errors = $chain->getErrorMessage();

        self::assertEquals(['test'], $result);
        self::assertEquals(null, $errors);
    }

    public function test_should_pass_null_to_response()
    {
        $chain = new ChainMapper([
            new class implements MapperInterface {
                public function supports(array $d, ContextInterface $context): bool {return true;}
                public function run(array $d, ContextInterface $context): ?array {return null;}
                public function getErrorMessage(): ?string { return null; }
                public function resetErrorMessage(): void {}
            }
        ]);

        $result = $chain->run(['test'], self::getMockBuilder(ContextInterface::class)->getMockForAbstractClass());
        $errors = $chain->getErrorMessage();

        self::assertEquals(null, $result);
        self::assertEquals(null, $errors);
    }

    public function test_should_check_mapper_supports()
    {
        $chain = new ChainMapper([
            new class implements MapperInterface {
                public function supports(array $d, ContextInterface $context): bool {return false;}
                public function run(array $d, ContextInterface $context): ?array {return ['one'];}
                public function getErrorMessage(): ?string { return 'one error'; }
                public function resetErrorMessage(): void {}
            },
            new class implements MapperInterface {
                public function supports(array $d, ContextInterface $context): bool {return true;}
                public function run(array $d, ContextInterface $context): ?array {return ['two'];}
                public function getErrorMessage(): ?string { return 'two error'; }
                public function resetErrorMessage(): void {}
            },
            new class implements MapperInterface {
                public function supports(array $d, ContextInterface $context): bool {return true;}
                public function run(array $d, ContextInterface $context): ?array {return ['three'];}
                public function getErrorMessage(): ?string { return 'three error'; }
                public function resetErrorMessage(): void {}
            }
        ]);

        $result = $chain->supports([], self::getMockBuilder(ContextInterface::class)->getMockForAbstractClass());
        $errors = $chain->getErrorMessage();
        self::assertEquals(true, $result);
        self::assertEquals(null, $errors);


        $chain = new ChainMapper([]);
        $result = $chain->supports([], self::getMockBuilder(ContextInterface::class)->getMockForAbstractClass());
        self::assertEquals(false, $result);
        self::assertEquals(null, $errors);
    }
}