<?php

namespace Invoker\Test;

use Invoker\Invoker;
use Invoker\Test\Mock\ArrayContainer;
use Invoker\Test\Mock\CallableSpy;

class InvokerTest extends \PHPUnit_Framework_TestCase
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
    public function should_invoke_callable()
    {
        $callable = new CallableSpy;

        $this->invoker->call($callable);

        $this->assertWasCalled($callable);
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
     * @expectedException \RuntimeException
     * @expectedExceptionMessage Unable to invoke the callable because no value was given for parameter 1 ($foo)
     */
    public function should_throw_if_no_value_for_parameter()
    {
        $this->invoker->call(function ($foo) {});
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
