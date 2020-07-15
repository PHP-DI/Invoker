<?php

namespace Invoker\Test;

use Invoker\CallableResolver;
use Invoker\Exception\NotCallableException;
use Invoker\Test\Mock\ArrayContainer;
use Invoker\Test\Mock\CallableSpy;
use PHPUnit\Framework\TestCase;
use stdClass;

class CallableResolverTest extends TestCase
{
    /**
     * @var CallableResolver
     */
    private $resolver;

    /**
     * @var ArrayContainer
     */
    private $container;

    public function setUp(): void
    {
        parent::setUp();
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
        $this->container->set(CallableSpy::class, $callable);

        $this->assertSame($callable, $this->resolver->resolve(CallableSpy::class));
    }

    /**
     * @test
     */
    public function resolve_array_method_call()
    {
        $fixture = new InvokerTestFixture;
        $this->container->set(InvokerTestFixture::class, $fixture);

        $result = $this->resolver->resolve(array(InvokerTestFixture::class, 'foo'));

        $result();
        $this->assertTrue($fixture->wasCalled);
    }

    /**
     * @test
     */
    public function resolve_string_method_call()
    {
        $fixture = new InvokerTestFixture;
        $this->container->set(InvokerTestFixture::class, $fixture);

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
     */
    public function resolve_invoke_class_string_factory_without_register()
    {
        $result = $this->resolver->resolve(InvokerTestClassString::class);
        $this->assertInstanceOf(\StdClass::class, $result());
    }

    /**
     * @test
     */
    public function resolve_magic_call_static()
    {
        $result = $this->resolver->resolve([InvokerTestCallStaticMagic::class, 'test']);
        $result = $result();
        $this->assertInstanceOf(\StdClass::class, $result);
        $this->assertEquals('test', $result->name);
    }

    /**
     * @test
     */
    public function throws_resolving_non_callable_from_container()
    {
        $this->expectExceptionMessage("'foo' is neither a callable nor a valid container entry");
        $this->expectException(NotCallableException::class);
        $resolver = new CallableResolver(new ArrayContainer);
        $resolver->resolve('foo');
    }

    /**
     * @test
     */
    public function handles_objects_correctly_in_exception_message()
    {
        $this->expectExceptionMessage("Instance of stdClass is not a callable");
        $this->expectException(NotCallableException::class);
        $resolver = new CallableResolver(new ArrayContainer);
        $resolver->resolve(new stdClass);
    }

    /**
     * @test
     */
    public function handles_method_calls_correctly_in_exception_message()
    {
        $this->expectExceptionMessage("stdClass::test() is not a callable");
        $this->expectException(NotCallableException::class);
        $resolver = new CallableResolver(new ArrayContainer);
        $resolver->resolve(array(new stdClass, 'test'));
    }
}

function foo()
{
    return 'bar';
}

class InvokerTestClassString
{
    public function __invoke()
    {
        return new StdClass();
    }
}

class InvokerTestCallStaticMagic
{
    private $text;

    final public function __construct(string $name)
    {
        $this->name = $name;
    }

    public function __invoke()
    {
        $class = new StdClass();
        $class->name = $this->name;
        return $class;
    }

    public static function __callStatic(string $name, array $arguments): object
    {
        return \call_user_func_array(new static($name), $arguments);
    }
}