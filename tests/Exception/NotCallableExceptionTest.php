<?php declare(strict_types=1);

namespace Invoker\Test\Exception;

use Invoker\Exception\NotCallableException;
use PHPUnit\Framework\TestCase;
use stdClass;

class NotCallableExceptionTest extends TestCase
{
    /**
     * @test
     */
    #[\PHPUnit\Framework\Attributes\Test]
    public function handles_object_with_circular_reference()
    {
        $obj = new stdClass();
        $obj->self = $obj;

        $exception = NotCallableException::fromInvalidCallable($obj);

        $this->assertSame('Instance of stdClass is not a callable', $exception->getMessage());
    }

    /**
     * @test
     */
    #[\PHPUnit\Framework\Attributes\Test]
    public function handles_array_with_object_and_null_method()
    {
        $obj = new stdClass();

        $exception = NotCallableException::fromInvalidCallable([$obj, null]);

        $this->assertSame('stdClass::() is not a callable.', $exception->getMessage());
    }

    /**
     * @test
     */
    #[\PHPUnit\Framework\Attributes\Test]
    public function handles_circular_array_reference()
    {
        $a = [];
        $a[] = &$a;

        $exception = NotCallableException::fromInvalidCallable($a);

        $this->assertStringContainsString('is not a callable', $exception->getMessage());
    }

    /**
     * @test
     */
    #[\PHPUnit\Framework\Attributes\Test]
    public function handles_circular_array_reference_as_container_entry()
    {
        $a = [];
        $a[] = &$a;

        $exception = NotCallableException::fromInvalidCallable($a, true);

        $this->assertStringContainsString('is neither a callable nor a valid container entry', $exception->getMessage());
    }
}
