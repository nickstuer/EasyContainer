<?php

namespace NickStuer\EasyContainer;

class Container
{
    private $classArgs = [];

    private $inProcessArgs = [];

    private $inProcessClasses = [];

    private $sharedObjects = [];

    public function __construct()
    {

    }

    public function define($className, $args)
    {
        foreach($args as $argName => $argValue) {
            $this->classArgs[$className][$argName] = $argValue;
        }
    }

    public function share($objectOrClass)
    {
        if (is_object($objectOrClass)) {
            $className = get_class($objectOrClass);
            $this->sharedObjects[$className] = $objectOrClass;
        } else {
            $this->sharedObjects[$objectOrClass] = $this->make($objectOrClass);
        }
    }

    public function make($className, $args = [])
    {
        if (isset($this->sharedObjects[$className])) {
            return $this->sharedObjects[$className];
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

                if (isset($this->sharedObjects[$newClassName])) {
                    $newInstanceParams[] = $this->sharedObjects[$newClassName];
                } else {
                    echo "Here: " . $newClassName . "<br>";
                    $newInstanceParams[] = $this->resolve($newClassName);
                }
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
