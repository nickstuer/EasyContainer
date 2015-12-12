<?php

namespace NickStuer\EasyContainer;

class Container
{
    private $classArgs = [];

    private $inProcessArgs = [];

    private $sharedClasses = [];

    public function __construct()
    {

    }

    public function define($className, $args)
    {
        foreach($args as $argName => $argValue) {
            $this->classArgs[$className][$argName] = $argValue;
        }
    }

    public function share($instance)
    {
        if (is_object($instance)) {
            $className = get_class($instance);
            $this->sharedClasses[$className] = $instance;
        }
    }

    public function make($className, $args = [])
    {
        if (isset($this->sharedClasses[$className])) {
            return $this->sharedClasses[$className];
        }

        $this->inProcessArgs = [];

        foreach($args as $argName => $argValue) {
            $this->inProcessArgs[$argName] = $argValue;
        }

        if (isset($this->classArgs[$className])) {
            foreach ($this->classArgs[$className] as $argName => $argValue) {
                $this->inProcessArgs[$argName] = $argValue;
            }
        }

        return $this->resolve($className);
    }

    protected function resolve($className)
    {
        $reflectionClass = new \ReflectionClass($className);
        $constructor = $reflectionClass->getConstructor();

        if (is_null($constructor)) {
            return new $className;
        }

        $params = $constructor->getParameters();

        if (count($params) === 0) {
            return new $className;
        }

        $newInstanceParams = [];

        foreach ($params as $param) {
            if (!is_null($param->getClass())) {
                $newInstanceParams[] = $this->resolve( $param->getClass()->getName() );
            } else {
                $paramName = $param->getName();
                $newInstanceParams[] = $this->inProcessArgs[$paramName];
            }
        }

        return $reflectionClass->newInstanceArgs($newInstanceParams);
    }
}