<?php
/** @noinspection PhpUnhandledExceptionInspection */

namespace Invoker\ParameterResolver;

use ArrayIterator;
use EmptyIterator;
use Iterator;
use PHPUnit\Framework\TestCase;
use ReflectionFunction;

/**
 * @requires PHP 5.6
 */
class TypeHintVariadicResolverTest extends TestCase
{
    private function assertParams(array $expected, array $provided, callable $callable, GeneratorResolver $resolver)
    {
        $resolver = new ResolverChain([$resolver]);
        $this->assertSame($expected, $resolver->getParameters(new ReflectionFunction($callable), $provided, []));
    }

    /**
     * @covers TypeHintVariadicResolver::__invoke
     */
    public function testInvokeNoHint()
    {
        $fn = function($a, ...$b) {return \func_get_args();};
        $this->assertParams([], ['b' => 'B', 'a' => 'A'], $fn, new TypeHintVariadicResolver);
        $this->assertParams([], ['b' => 'B', 'a' => 'A'], $fn, new TypeHintVariadicResolver(true));
    }

    /**
     * @covers TypeHintVariadicResolver::__invoke
     */
    public function testInvokeArray()
    {
        $fn       = function($a, array ...$b) {return func_get_args();};
        $provided = ['b' => ['B'], 'a' => $this, ['C']];
        $this->assertParams([1 => ['B'], ['C']], $provided, $fn, new TypeHintVariadicResolver);
        $this->assertParams([1 => ['C']], $provided, $fn, new TypeHintVariadicResolver(true));
    }

    /**
     * @covers TypeHintVariadicResolver::__invoke
     */
    public function testInvokeCallable()
    {
        $fn       = function($a, callable ...$b) {return func_get_args();};
        $provided = ['b' => $b = function() {}, 'a' => $this, $c = [$this, 'testInvokeCallable']];
        $this->assertParams([1 => $b, $c], $provided, $fn, new TypeHintVariadicResolver);
        $this->assertParams([1 => $c], $provided, $fn, new TypeHintVariadicResolver(true));
    }

    /**
     * @covers TypeHintVariadicResolver::__invoke
     */
    public function testInvokeClass()
    {
        $fn       = function($a, Iterator ...$b) {return func_get_args();};
        $provided = ['b' => $b = new ArrayIterator, 'a' => $this, $c = new EmptyIterator];
        $this->assertParams([1 => $b, $c], $provided, $fn, new TypeHintVariadicResolver);
        $this->assertParams([1 => $c], $provided, $fn, new TypeHintVariadicResolver(true));
    }
}
