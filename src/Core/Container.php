<?php
namespace App\Core;

class Container
{
    private array $bindings = [];
    private array $instances = [];

    public function set(string $key, $value): void
    {
        if (!$value instanceof \Closure) {
            $this->instances[$key] = $value;
            return;
        }
        $this->bindings[$key] = $value;
    }

    public function get(string $key)
    {
        if (array_key_exists($key, $this->instances)) {
            return $this->instances[$key];
        }
        if (array_key_exists($key, $this->bindings)) {
            $instance = $this->bindings[$key]($this);
            $this->instances[$key] = $instance;
            return $instance;
        }
        throw new \Exception("Service [$key] is not registered.");
    }
    public function resolve(string $service, $method)
    {
        return function (...$args) use ($service, $method) {
            return $this
                        ->get($service)
                ->{$method}(...$args);
        };
    }
}
