<?php
/** @noinspection PhpUnhandledExceptionInspection */

namespace Invoker\ParameterResolver;

use Invoker\Invoker;

/**
 */
class VariadicResolverTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @covers VariadicResolver::__invoke
     */
    public function testInvoke()
    {
        $resolver = new ResolverChain([new VariadicResolver, new AssociativeArrayResolver]);
        $invoker  = new Invoker($resolver);
        $callable = function($p1, ...$p2) {
            return \func_get_args();
        };
        $ref      = new \ReflectionFunction($callable);

        // parameter provided as array
        $provided = ['p2' => ['P21', 'P22'], 'p1' => 'P1'];
        $this->assertSame([1 => 'P21', 2 => 'P22', 0 => 'P1'], $resolver->getParameters($ref, $provided, []));
        $this->assertSame(['P1', 'P21', 'P22'], $invoker->call($callable, $provided));

        // parameter provided as non-array => cast
        $provided = ['p1' => 'P1', 'p2' => 'P2'];
        $this->assertSame([1 => 'P2', 0 => 'P1'], $resolver->getParameters($ref, $provided, []));
        $this->assertSame(['P1', 'P2'], $invoker->call($callable, $provided));
    }
}
