<?php declare(strict_types=1);

namespace Invoker\Test;

use Invoker\CallableResolver;
use Invoker\Exception\NotCallableException;
use Invoker\Test\Mock\ArrayContainer;
use Invoker\Test\Mock\CallableSpy;
use PHPUnit\Framework\TestCase;
use stdClass;

class CallableResolverTest extends TestCase
{
    /** @var CallableResolver */
    private $resolver;

    /** @var ArrayContainer */
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
        $callable = function () {
        };
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

        $result = $this->resolver->resolve([InvokerTestFixture::class, 'foo']);

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

        $result = $this->resolver->resolve(['thing-to-call', 'foo']);

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
        $this->expectExceptionMessage('Instance of stdClass is not a callable');
        $this->expectException(NotCallableException::class);
        $resolver = new CallableResolver(new ArrayContainer);
        $resolver->resolve(new stdClass);
    }

    /**
     * @test
     */
    public function handles_method_calls_correctly_in_exception_message()
    {
        $this->expectExceptionMessage('stdClass::test() is not a callable');
        $this->expectException(NotCallableException::class);
        $resolver = new CallableResolver(new ArrayContainer);
        $resolver->resolve([new stdClass, 'test']);
    }
}

function foo()
{
    return 'bar';
}
