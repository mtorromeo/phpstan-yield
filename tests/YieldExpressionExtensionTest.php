<?php

declare(strict_types=1);

namespace PHPStanYield;

use PHPStan\Testing\TypeInferenceTestCase;
use PHPUnit\Framework\Attributes\DataProvider;

class YieldExpressionExtensionTest extends TypeInferenceTestCase
{
    // @phpstan-ignore missingType.iterableValue
    public static function dataFileAsserts(): iterable
    {
        yield from self::gatherAssertTypes(__DIR__ . '/data/yield-promise.php');
    }

    /**
     * @param mixed ...$args
     */
    #[DataProvider('dataFileAsserts')]
    public function testFileAsserts(string $assertType, string $file, ...$args): void
    {
        $this->assertFileAsserts($assertType, $file, ...$args);
    }

    public static function getAdditionalConfigFiles(): array
    {
        return [
            __DIR__ . '/../extension.neon',
        ];
    }
}
