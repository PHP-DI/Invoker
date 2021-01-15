<?php declare(strict_types=1);

namespace Invoker\Test\Reflection;

use Invoker\Reflection\CallableReflection;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;

class CallableReflectionTest extends TestCase
{
    public function test_with_not_real_PHP_callable_array()
    {
        $reflection = CallableReflection::create([self::class, 'foo']);

        $this->assertInstanceOf(ReflectionMethod::class, $reflection);
    }

    public function foo(): void
    {
    }
}
