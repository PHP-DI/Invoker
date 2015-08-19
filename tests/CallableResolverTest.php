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
    public function resolves_function_callable()
    {
        $result = $this->resolver->resolve('strlen');

        $this->assertSame(strlen('Hello world!'), $result('Hello world!'));
    }

    /**
     * @test
     */
    public function resolves_namespaced_function_callable()
    {
        $result = $this->resolver->resolve(__NAMESPACE__ . '\foo');

        $this->assertEquals('bar', $result());
    }

    /**
     * @test
     */
    public function resolves_callable_from_container()
    {
        $callable = function () {};
        $this->container->set('thing-to-call', $callable);

        $this->assertSame($callable, $this->resolver->resolve('thing-to-call'));
    }

    /**
     * @test
     */
    public function resolves_invokable_class_from_container()
    {
        $callable = new CallableSpy;
        $this->container->set('Invoker\Test\Mock\CallableSpy', $callable);

        $this->assertSame($callable, $this->resolver->resolve('Invoker\Test\Mock\CallableSpy'));
    }

    /**
     * @test
     */
    public function resolves_method_call_service_from_container()
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
    public function resolves_method_call_class_from_container()
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

function foo()
{
    return 'bar';
}
