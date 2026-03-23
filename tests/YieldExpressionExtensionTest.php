<?php

declare(strict_types=1);

namespace PHPStanYield;

use PHPStan\Testing\TypeInferenceTestCase;

class YieldExpressionExtensionTest extends TypeInferenceTestCase
{
    public function testYieldPromise(): void
    {
        foreach (self::gatherAssertTypes(__DIR__ . '/data/yield-promise.php') as $assert) {
            $this->assertFileAsserts($assert[0], $assert[1], ...array_slice($assert, 2));
        }
    }

    public static function getAdditionalConfigFiles(): array
    {
        return [
            __DIR__ . '/../extension.neon',
        ];
    }
}
