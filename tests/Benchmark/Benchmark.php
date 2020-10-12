<?php declare(strict_types=1);

namespace Invoker\Test\Benchmark;

use Athletic\AthleticEvent;
use Invoker\Invoker;

class Benchmark extends AthleticEvent
{
    /** @var Invoker */
    private $invoker;

    private $noop;

    public function classSetUp()
    {
        $this->invoker = new Invoker;
        $this->noop = new NoopClass;
    }

    /**
     * @baseline
     * @iterations 100000
     */
    public function native_invoke_closure()
    {
        call_user_func(function () {
            // call-target, intenionally left empty
        });
    }

    /**
     * @iterations 100000
     */
    public function native_invoke_method()
    {
        call_user_func([$this->noop, 'noop']);
    }

    /**
     * @iterations 100000
     */
    public function invoke_closure()
    {
        $this->invoker->call(function () {
            // call-target, intenionally left empty
        });
    }

    /**
     * @iterations 100000
     */
    public function invoke_method()
    {
        $this->invoker->call([$this->noop, 'noop']);
    }

    /**
     * @iterations 100000
     */
    public function invoke_with_named_parameters()
    {
        $this->invoker->call([$this->noop, 'namedNoop'], ['name' => 'foo']);
    }
}
