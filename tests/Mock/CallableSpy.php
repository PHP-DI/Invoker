<?php declare(strict_types=1);

namespace Invoker\Test\Mock;

/**
 * Mock a callable and spies being called.
 */
class CallableSpy
{
    /** @var callable|null */
    private $callable;

    /** @var int */
    private $callCount = 0;

    /** @var array */
    private $parameters = [];

    public static function mock($callable)
    {
        return new self($callable);
    }

    public function __construct($callable = null)
    {
        $this->callable = $callable;
    }

    public function __invoke()
    {
        $this->callCount++;
        $this->parameters = func_get_args();

        if ($this->callable === null) {
            return null;
        }

        return call_user_func_array($this->callable, func_get_args());
    }

    /**
     * @return int
     */
    public function getCallCount()
    {
        return $this->callCount;
    }

    /**
     * @return array
     */
    public function getLastCallParameters()
    {
        return $this->parameters;
    }
}
