<?php

namespace NickStuer\EasyContainer;

class Container
{
    private $classArgs = [];

    private $inProcessArgs = [];

    private $inProcessClasses = [];

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
        $this->inProcessClasses = [];

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
        if (isset($this->inProcessClasses[$className])) {
            throw new \Exception('Infinite Loop');
        }

        $this->inProcessClasses[$className] = true;

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

                $newClassName = $param->getClass()->getName();

                if (isset($this->sharedClasses[$newClassName])) {
                    echo 'hi';
                    return $this->sharedClasses[$newClassName];
                }

                $newInstanceParams[] = $this->resolve( $newClassName );
            } else {
                $paramName = $param->getName();

                if (isset($this->inProcessArgs[$paramName])) {
                    $newInstanceParams[] = $this->inProcessArgs[$paramName];
                }
            }
        }

        return $reflectionClass->newInstanceArgs($newInstanceParams);
    }
}