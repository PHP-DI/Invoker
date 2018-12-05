<?php

namespace Invoker\Test;

use Invoker\Invoker;
use Invoker\ParameterResolver\AssociativeArrayResolver;
use Invoker\ParameterResolver\Container\ParameterNameContainerResolver;
use Invoker\ParameterResolver\Container\TypeHintContainerResolver;
use Invoker\Test\Mock\ArrayContainer;
use Invoker\Test\Mock\CallableSpy;
use PHPUnit\Framework\TestCase;

class InvokerTest extends TestCase
{
    /**
     * @var Invoker
     */
    private $invoker;

    /**
     * @var ArrayContainer
     */
    private $container;

    public function setUp()
    {
        $this->container = new ArrayContainer;
        $this->invoker = new Invoker(null, $this->container);
    }

    /**
     * @test
     */
    public function should_invoke_closure()
    {
        $callable = new CallableSpy;

        $this->invoker->call($callable);

        $this->assertWasCalled($callable);
    }

    /**
     * @test
     */
    public function should_invoke_method()
    {
        $fixture = new InvokerTestFixture;

        $this->invoker->call(array($fixture, 'foo'));

        $this->assertTrue($fixture->wasCalled);
    }

    /**
     * @test
     * @expectedException \Invoker\Exception\NotCallableException
     * @expectedExceptionMessage Invoker\Test\InvokerTestFixture::bar() is not a callable.
     */
    public function cannot_invoke_unknown_method()
    {
        $this->invoker->call(array(new InvokerTestFixture, 'bar'));
    }

    /**
     * @test
     * @expectedException \Invoker\Exception\NotCallableException
     * @expectedExceptionMessage Invoker\Test\InvokerTestMagicMethodFixture::foo() is not a callable. A __call() method exists but magic methods are not supported.
     */
    public function cannot_invoke_magic_method()
    {
        $this->invoker->call(array(new InvokerTestMagicMethodFixture, 'foo'));
    }

    /**
     * @test
     */
    public function should_invoke_static_method()
    {
        $result = $this->invoker->call(array('Invoker\Test\InvokerTestStaticFixture', 'foo'));

        $this->assertEquals('bar', $result);
    }

    /**
     * @test
     */
    public function should_invoke_static_method_with_scope_resolution_syntax()
    {
        $result = $this->invoker->call('Invoker\Test\InvokerTestStaticFixture::foo');

        $this->assertEquals('bar', $result);
    }

    /**
     * @test
     */
    public function should_return_the_callable_return_value()
    {
        $result = $this->invoker->call(function () {
            return 42;
        });

        $this->assertEquals(42, $result);
    }

    /**
     * @test
     * @expectedException \Invoker\Exception\NotEnoughParametersException
     * @expectedExceptionMessage Unable to invoke the callable because no value was given for parameter 2 ($bar)
     */
    public function should_throw_if_no_value_for_parameter()
    {
        $this->invoker->call(function ($foo, $bar, $baz) {}, array(
            'foo' => 'foo',
            'baz' => 'baz',
        ));
    }

    /**
     * @test
     * @expectedException \Invoker\Exception\NotEnoughParametersException
     * @expectedExceptionMessage Unable to invoke the callable because no value was given for parameter 2 ($bar)
     */
    public function should_throw_if_no_value_for_parameter_even_with_trailing_optional_parameters()
    {
        $this->invoker->call(function ($foo, $bar, $baz = null) {}, array(
            'foo' => 'foo',
            'baz' => 'baz',
        ));
    }

    /**
     * @test
     */
    public function should_invoke_callable_with_parameters_indexed_by_position()
    {
        $callable = new CallableSpy;

        $this->invoker->call($callable, array('foo', 'bar'));

        $this->assertWasCalledWith($callable, array('foo', 'bar'));
    }

    /**
     * @test
     */
    public function should_invoke_callable_with_parameters_indexed_by_name()
    {
        $parameters = array(
            'foo' => 'foo',
            'bar' => 'bar',
        );

        $result = $this->invoker->call(function ($foo, $bar) {
            return $foo . $bar;
        }, $parameters);

        $this->assertEquals('foobar', $result);
    }

    /**
     * @test
     */
    public function should_invoke_callable_with_default_value_for_undefined_parameters()
    {
        $parameters = array(
            'foo', // Positionned parameter
            'baz' => 'baz', // Named parameter
        );

        $result = $this->invoker->call(function ($foo, $bar = 'bar', $baz = null) {
            return $foo . $bar . $baz;
        }, $parameters);

        $this->assertEquals('foobarbaz', $result);
    }

    /**
     * @test
     */
    public function should_do_dependency_injection_with_typehint_container_resolver()
    {
        $resolver = new TypeHintContainerResolver($this->container);
        $this->invoker->getParameterResolver()->prependResolver($resolver);

        $expected = new \stdClass();
        $this->container->set('stdClass', $expected);

        $result = $this->invoker->call(function (\stdClass $foo) {
            return $foo;
        });

        $this->assertSame($expected, $result);
    }

    /**
     * @test
     */
    public function should_do_dependency_injection_with_parameter_name_container_resolver()
    {
        $resolver = new ParameterNameContainerResolver($this->container);
        $this->invoker->getParameterResolver()->prependResolver($resolver);

        $expected = new \stdClass();
        $this->container->set('foo', $expected);

        $result = $this->invoker->call(function ($foo) {
            return $foo;
        });

        $this->assertSame($expected, $result);
    }

    /**
     * @test
     */
    public function should_resolve_callable_from_container()
    {
        $callable = new CallableSpy;
        $this->container->set('thing-to-call', $callable);

        $this->invoker->call('thing-to-call');

        $this->assertWasCalled($callable);
    }

    /**
     * @test
     */
    public function should_resolve_array_callable_from_container()
    {
        $fixture = new InvokerTestFixture;
        $this->container->set('thing-to-call', $fixture);

        $result = $this->invoker->call(array('thing-to-call', 'foo'));

        $this->assertEquals('bar', $result);
        $this->assertTrue($fixture->wasCalled);
    }

    /**
     * @test
     */
    public function should_resolve_callable_from_container_with_scope_resolution_syntax()
    {
        $fixture = new InvokerTestFixture;
        $this->container->set('thing-to-call', $fixture);

        $result = $this->invoker->call('thing-to-call::foo');

        $this->assertEquals('bar', $result);
        $this->assertTrue($fixture->wasCalled);
    }

    /**
     * @test
     */
    public function should_resolve_array_callable_from_container_with_class_name()
    {
        $fixture = new InvokerTestFixture;
        $this->container->set('Invoker\Test\InvokerTestFixture', $fixture);

        $result = $this->invoker->call(array('Invoker\Test\InvokerTestFixture', 'foo'));

        $this->assertEquals('bar', $result);
        $this->assertTrue($fixture->wasCalled);
    }

    /**
     * @test
     */
    public function should_resolve_callable_from_container_with_class_name_in_scope_resolution_syntax()
    {
        $fixture = new InvokerTestFixture;
        $this->container->set('Invoker\Test\InvokerTestFixture', $fixture);

        $result = $this->invoker->call('Invoker\Test\InvokerTestFixture::foo');

        $this->assertEquals('bar', $result);
        $this->assertTrue($fixture->wasCalled);
    }

    /**
     * @test
     * @expectedException \Invoker\Exception\NotCallableException
     * @expectedExceptionMessage Cannot call Invoker\Test\InvokerTestFixture::foo() because foo() is not a static method and "Invoker\Test\InvokerTestFixture" is not a container entry
     */
    public function should_not_invoke_statically_a_non_static_method()
    {
        $this->invoker->call(array('Invoker\Test\InvokerTestFixture', 'foo'));
    }

    /**
     * @test
     * @expectedException \Invoker\Exception\NotCallableException
     * @expectedExceptionMessage 'foo' is not a callable
     */
    public function should_throw_if_calling_non_callable_without_container()
    {
        $invoker = new Invoker();
        $invoker->call('foo');
    }

    /**
     * @test
     * @expectedException \Invoker\Exception\NotCallableException
     * @expectedExceptionMessage NULL is not a callable
     */
    public function should_throw_if_calling_non_callable_without_container_2()
    {
        $invoker = new Invoker();
        $invoker->call(null);
    }

    /**
     * @test
     * @expectedException \Invoker\Exception\NotCallableException
     * @expectedExceptionMessage 'foo' is neither a callable nor a valid container entry
     */
    public function should_throw_if_calling_non_callable_with_container()
    {
        $invoker = new Invoker(null, new ArrayContainer);
        $invoker->call('foo');
    }

    /**
     * @test
     * @expectedException \Invoker\Exception\NotCallableException
     * @expectedExceptionMessage Instance of stdClass is not a callable
     */
    public function should_throw_if_calling_non_callable_object()
    {
        $invoker = new Invoker();
        $invoker->call(new \stdClass());
    }

    /**
     * @test
     */
    public function should_invoke_static_method_rather_than_resolving_entry_from_container()
    {
        // Register a non-callable so that test fails if we try to invoke that
        $this->container->set('Invoker\Test\InvokerTestStaticFixture', 'foobar');

        // Call the static method: shouldn't get from the container even though the
        // entry exist (because we are calling a static method)
        $result = $this->invoker->call(array('Invoker\Test\InvokerTestStaticFixture', 'foo'));
        $this->assertEquals('bar', $result);
    }

    /**
     * @test
     * @expectedException \Invoker\Exception\NotEnoughParametersException
     * @expectedExceptionMessage Unable to invoke the callable because no value was given for parameter 2 ($bar)
     */
    public function should_throw_if_no_value_for_optional_parameter_1()
    {
        // Create without the DefaultValueResolver
        $this->invoker = new Invoker(new AssociativeArrayResolver, $this->container);
        $this->invoker->call(function ($foo, $bar = null) {}, array(
            'foo' => 'foo',
        ));
    }

    /**
     * @test
     * @expectedException \Invoker\Exception\NotEnoughParametersException
     * @expectedExceptionMessage Unable to invoke the callable because no value was given for parameter 2 ($bar)
     */
    public function should_throw_if_no_value_for_optional_parameter_2()
    {
        // Create without the DefaultValueResolver
        $this->invoker = new Invoker(new AssociativeArrayResolver, $this->container);
        $this->invoker->call(function ($foo, $bar = null, $baz = null) {}, array(
            'foo' => 'foo',
            'baz' => 'baz',
        ));
    }

    private function assertWasCalled(CallableSpy $callableSpy)
    {
        $this->assertEquals(1, $callableSpy->getCallCount(), 'The callable should be called once');
    }

    private function assertWasCalledWith(CallableSpy $callableSpy, array $parameters)
    {
        $this->assertWasCalled($callableSpy);
        $this->assertEquals($parameters, $callableSpy->getLastCallParameters());
    }
}

class InvokerTestFixture
{
    public $wasCalled = false;
    public function foo()
    {
        // Use this to make sure we are not called from a static context
        $this->wasCalled = true;
        return 'bar';
    }
}

class InvokerTestStaticFixture
{
    public static function foo()
    {
        return 'bar';
    }
}

class InvokerTestMagicMethodFixture
{
    public $wasCalled = false;
    public function __call($name, $args)
    {
        if ($name === 'foo') {
            $this->wasCalled = true;
            return 'bar';
        }
        throw new \Exception('Unknown method');
    }
}
