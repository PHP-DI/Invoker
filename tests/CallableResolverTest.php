<?php

namespace Invoker\Test;

use Invoker\CallableResolver;
use Invoker\Test\Mock\ArrayContainer;
use Invoker\Test\Mock\CallableSpy;

class CallableResolverTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var CallableResolver
     */
    private $resolver;

    /**
     * @var ArrayContainer
     */
    private $container;

    public function setUp()
    {
        $this->container = new ArrayContainer;
        $this->resolver = new CallableResolver($this->container);
    }

    /**
     * @test
     */
    public function resolves_callable_from_container()
    {
        $callable = new CallableSpy;
        $this->container->set('thing-to-call', $callable);

        $this->assertSame($callable, $this->resolver->resolve('thing-to-call'));
    }

    /**
     * @test
     */
    public function resolves_array_callable_from_container()
    {
        $fixture = new InvokerTestFixture;
        $this->container->set('thing-to-call', $fixture);

        $result = $this->resolver->resolve(array('thing-to-call', 'foo'));

        $result();
        $this->assertTrue($fixture->wasCalled);
    }

    /**
     * @test
     */
    public function resolve_array_callable_from_container_with_class_name()
    {
        $fixture = new InvokerTestFixture;
        $this->container->set('Invoker\Test\InvokerTestFixture', $fixture);

        $result = $this->resolver->resolve(array('Invoker\Test\InvokerTestFixture', 'foo'));

        $result();
        $this->assertTrue($fixture->wasCalled);
    }

    /**
     * @test
     * @expectedException \Invoker\Exception\NotCallableException
     * @expectedExceptionMessage foo is neither a callable or a valid container entry
     */
    public function throws_resolving_non_callable_from_container()
    {
        $resolver = new CallableResolver(new ArrayContainer);
        $resolver->resolve('foo');
    }
}
