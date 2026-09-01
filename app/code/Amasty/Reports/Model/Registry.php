<?php

declare(strict_types=1);

namespace Amasty\Reports\Model;

class Registry
{
    /**
     * @var array
     */
    private $registry = [];

    /**
     * Retrieve a value from registry by a key
     *
     * @param string $key
     * @return mixed
     */
    public function registry(string $key)
    {
        if (isset($this->registry[$key])) {
            return $this->registry[$key];
        }
        return null;
    }

    /**
     * Register a new variable
     *
     * @param string $key
     * @param mixed $value
     * @param bool $graceful
     * @return void
     * @throws \RuntimeException
     */
    public function register(string $key, $value, bool $graceful = false)
    {
        if (isset($this->registry[$key])) {
            if ($graceful) {
                return;
            }
            throw new \RuntimeException('Registry key "' . $key . '" already exists');
        }
        $this->registry[$key] = $value;
    }

    public function unregister(string $key)
    {
        if (isset($this->registry[$key])) {
            if (is_object($this->registry[$key])
                && method_exists($this->registry[$key], '__destruct')
                && is_callable([$this->registry[$key], '__destruct'])
            ) {
                $this->registry[$key]->__destruct();
            }
            unset($this->registry[$key]);
        }
    }

    public function __destruct()
    {
        $keys = array_keys($this->registry);
        array_walk($keys, [$this, 'unregister']);
    }
}
