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

/**
 * @template T
 * @yield T
 */
interface PromiseInterface {}

/**
 * @template T
 * @implements PromiseInterface<T>
 */
class ConcretePromise implements PromiseInterface {}

/**
 * @template T
 * @yield T
 */
class BasePromise {}

/**
 * @template T
 * @extends BasePromise<T>
 */
class ChildPromise extends BasePromise {}

/** @return ConcretePromise<int> */
function fetchIntFromInterface(): ConcretePromise
{
    return new ConcretePromise();
}

/** @return ChildPromise<bool> */
function fetchBoolFromChild(): ChildPromise
{
    return new ChildPromise();
}

function yieldPromiseLike(): Generator
{
    assertType('string', yield fetchString());
    assertType('array{int, non-empty-string}', yield fetchComplexArray());
    assertType('float', yield fetchGenericCall(1.24));
    assertType('int', yield fetchIntFromInterface());
    assertType('bool', yield fetchBoolFromChild());
}
