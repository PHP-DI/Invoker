<?php

namespace Invoker\Test\Benchmark;

use Athletic\AthleticEvent;
use Invoker\Test\Mock\ArrayContainer;
use Invoker\Invoker;

class Benchmark extends AthleticEvent
{
    /**
     * @var Invoker
     */
    private $invoker;

    private $noop;

    public function classSetUp() {
        $container = new ArrayContainer;

        $this->invoker = new Invoker(null, $container);
        $this->noop = new Noop();
    }

    /**
     * @baseline
     * @iterations 100
     */
    public function callUserFunc()
    {
        call_user_func(function() {
            // call-target, intenionally left empty
        });
    }

    /**
     * @iterations 100
     */
    public function callUserMethod()
    {
        call_user_func([$this->noop, 'noop']);
    }

    /**
     * @iterations 100
     */
    public function invokerCallUserFunc()
    {
        $this->invoker->call(function() {
           // call-target, intenionally left empty
        });
    }

    /**
     * @iterations 100
     */
    public function invokerCallUserMethod()
    {
        $this->invoker->call([$this->noop, 'noop']);
    }

    /**
     * @iterations 100
     */
    public function invokerCallNamedMethod()
    {
        $this->invoker->call([$this->noop, 'namedNoop'], ['name' => 'foo']);
    }

    /**
     * @iterations 100
     */
    public function invokerCallTypehintedMethod()
    {
        $this->invoker->call([$this->noop, 'typehintedNoop'], ['noop' => new Noop()]);
    }
}