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
    public function resolves_function()
    {
        $result = $this->resolver->resolve('strlen');

        $this->assertSame(strlen('Hello world!'), $result('Hello world!'));
    }

    /**
     * @test
     */
    public function resolves_namespaced_function()
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
    public function resolves_invokable_class()
    {
        $callable = new CallableSpy;
        $this->container->set('Invoker\Test\Mock\CallableSpy', $callable);

        $this->assertSame($callable, $this->resolver->resolve('Invoker\Test\Mock\CallableSpy'));
    }

    /**
     * @test
     */
    public function resolve_array_method_call()
    {
        $fixture = new InvokerTestFixture;
        $this->container->set('Invoker\Test\InvokerTestFixture', $fixture);

        $result = $this->resolver->resolve(array('Invoker\Test\InvokerTestFixture', 'foo'));

        $result();
        $this->assertTrue($fixture->wasCalled);
    }

    /**
     * @test
     */
    public function resolve_string_method_call()
    {
        $fixture = new InvokerTestFixture;
        $this->container->set('Invoker\Test\InvokerTestFixture', $fixture);

        $result = $this->resolver->resolve('Invoker\Test\InvokerTestFixture::foo');

        $result();
        $this->assertTrue($fixture->wasCalled);
    }

    /**
     * @test
     */
    public function resolves_array_method_call_with_service()
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
    public function resolves_string_method_call_with_service()
    {
        $fixture = new InvokerTestFixture;
        $this->container->set('thing-to-call', $fixture);

        $result = $this->resolver->resolve('thing-to-call::foo');

        $result();
        $this->assertTrue($fixture->wasCalled);
    }

    /**
     * @test
     * @expectedException \Invoker\Exception\NotCallableException
     * @expectedExceptionMessage 'foo' is neither a callable nor a valid container entry
     */
    public function throws_resolving_non_callable_from_container()
    {
        $resolver = new CallableResolver(new ArrayContainer);
        $resolver->resolve('foo');
    }

    /**
     * @test
     * @expectedException \Invoker\Exception\NotCallableException
     * @expectedExceptionMessage Instance of stdClass is not a callable
     */
    public function handles_objects_correctly_in_exception_message()
    {
        $resolver = new CallableResolver(new ArrayContainer);
        $resolver->resolve(new \stdClass);
    }

    /**
     * @test
     * @expectedException \Invoker\Exception\NotCallableException
     * @expectedExceptionMessage stdClass::test() is not a callable
     */
    public function handles_method_calls_correctly_in_exception_message()
    {
        $resolver = new CallableResolver(new ArrayContainer);
        $resolver->resolve(array(new \stdClass, 'test'));
    }
}

function foo()
{
    return 'bar';
}
