<?php

// api/src/Core/Container.php - Dependency Injection Container

declare(strict_types=1);

namespace App\Core;

class Container
{
    private array $bindings = [];
    private array $instances = [];

    public function bind(string $abstract, $concrete = null): void
    {
        $this->bindings[$abstract] = $concrete ?? $abstract;
    }

    public function singleton(string $abstract, $concrete = null): void
    {
        $this->bind($abstract, $concrete);
        $this->instances[$abstract] = null;
    }

    public function get(string $abstract)
    {
        // Return existing singleton instance
        if (array_key_exists($abstract, $this->instances)) {
            if ($this->instances[$abstract] === null) {
                $this->instances[$abstract] = $this->resolve($abstract);
            }
            return $this->instances[$abstract];
        }

        return $this->resolve($abstract);
    }

    private function resolve(string $abstract)
    {
        $concrete = $this->bindings[$abstract] ?? $abstract;

        if ($concrete instanceof \Closure) {
            return $concrete($this);
        }

        if (is_string($concrete)) {
            return $this->build($concrete);
        }

        return $concrete;
    }

    private function build(string $className)
    {
        $reflection = new \ReflectionClass($className);

        if (!$reflection->isInstantiable()) {
            throw new \Exception("Class {$className} is not instantiable");
        }

        $constructor = $reflection->getConstructor();

        if ($constructor === null) {
            return new $className;
        }

        $parameters = $constructor->getParameters();
        $dependencies = [];

        foreach ($parameters as $parameter) {
            $type = $parameter->getType();

            if ($type === null) {
                if ($parameter->isDefaultValueAvailable()) {
                    $dependencies[] = $parameter->getDefaultValue();
                } else {
                    throw new \Exception("Cannot resolve parameter {$parameter->getName()}");
                }
            } else {
                $dependencies[] = $this->get($type->getName());
            }
        }

        return $reflection->newInstanceArgs($dependencies);
    }
}