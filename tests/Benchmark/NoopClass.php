<?php declare(strict_types=1);

namespace Invoker\Test\Benchmark;

class NoopClass
{
    public function noop()
    {
        // call-target, intenionally left empty
    }

    public function namedNoop(string $name)
    {
        // call-target, intenionally left empty
    }
}
