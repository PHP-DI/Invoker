<?php

namespace Invoker\Test;

class TestObject
{
    private $a;
    
    private $b;
    
    private $c;
    
    public function __construct($a, $b, $c)
    {
        $this->a = $a;
        $this->b = $b;
        $this->c = $c;
    }
    
    public function getA()
    {
        return $this->a;
    }
    
    public function getB()
    {
        return $this->b;
    }
    
    public function getC()
    {
        return $this->c;
    }
}
