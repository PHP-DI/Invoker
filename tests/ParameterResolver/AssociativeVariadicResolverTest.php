<?php
/** @noinspection PhpUnhandledExceptionInspection */

namespace Invoker\ParameterResolver;

use Invoker\Invoker;
use PHPUnit\Framework\TestCase;
use ReflectionFunction;

/**
 * @requires PHP 5.6
 */
class AssociativeVariadicResolverTest extends TestCase
{
    /**
     * @covers AssociativeVariadicResolver::__invoke
     */
    public function testInvoke()
    {
        $callable = function($p1, ...$p2) {
            return \func_get_args();
        };
        $ref = new ReflectionFunction($callable);

        $resolverCast = new ResolverChain([new AssociativeVariadicResolver, new AssociativeArrayResolver]);
        $invokerCast  = new Invoker($resolverCast);
        $resolver     = new ResolverChain([new AssociativeVariadicResolver(false), new AssociativeArrayResolver]);
        $invoker      = new Invoker($resolver);

        // parameter provided as array
        $provided = ['p2' => ['P21', 'P22'], 'p1' => 'P1'];
        $this->assertSame([1 => 'P21', 2 => 'P22', 0 => 'P1'], $resolverCast->getParameters($ref, $provided, []));
        $this->assertSame(['P1', 'P21', 'P22'], $invokerCast->call($callable, $provided));
        $this->assertSame([1 => ['P21', 'P22'], 0 => 'P1'], $resolver->getParameters($ref, $provided, []));
        $this->assertSame(['P1', ['P21', 'P22']], $invoker->call($callable, $provided));

        // parameter provided as non-array => cast
        $provided = ['p1' => 'P1', 'p2' => 'P2'];
        $this->assertSame([1 => 'P2', 0 => 'P1'], $resolverCast->getParameters($ref, $provided, []));
        $this->assertSame(['P1', 'P2'], $invokerCast->call($callable, $provided));
        $this->assertSame([1 => 'P2', 0 => 'P1'], $resolver->getParameters($ref, $provided, []));
        $this->assertSame(['P1', 'P2'], $invoker->call($callable, $provided));


        // parameter is not provided => no exception
        $provided = ['p1' => 'P1'];
        $this->assertSame(['P1'], $resolverCast->getParameters($ref, $provided, []));
        $this->assertSame(['P1'], $invokerCast->call($callable, $provided));
        $this->assertSame(['P1'], $resolver->getParameters($ref, $provided, []));
        $this->assertSame(['P1'], $invoker->call($callable, $provided));
    }
}
