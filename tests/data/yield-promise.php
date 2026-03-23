<?php

namespace YieldPromise;

use Generator;

use function PHPStan\Testing\assertType;

/**
 * @template T
 * @yield T
 */
final class TemplatedPromiseLike {}

/** @return TemplatedPromiseLike<string> */
function fetchString(): TemplatedPromiseLike
{
    return new TemplatedPromiseLike();
}

/** @return TemplatedPromiseLike<array{int, non-empty-string}> */
function fetchComplexArray(): TemplatedPromiseLike
{
    return new TemplatedPromiseLike();
}

/**
 * @template T
 * @param T $param
 * @return TemplatedPromiseLike<T>
 */
function fetchGenericCall($param): TemplatedPromiseLike
{
    return new TemplatedPromiseLike();
}

function yieldPromiseLike(): Generator
{
    assertType('string', yield fetchString());
    assertType('array{int, non-empty-string}', yield fetchComplexArray());
    assertType('float', yield fetchGenericCall(1.24));
}
