<?php

namespace ipl\Stdlib;

/**
 * Store and retrieve arbitrary key-value data
 */
class Data
{
    /** @var array<string, mixed> */
    protected array $data = [];

    /**
     * Check whether there's any data
     *
     * @return bool
     */
    public function isEmpty(): bool
    {
        return empty($this->data);
    }

    /**
     * Check whether the given data exists
     *
     * @param string $name The name of the data
     *
     * @return bool
     */
    public function has(string $name): bool
    {
        return array_key_exists($name, $this->data);
    }

    /**
     * Get the value of the given data
     *
     * @param string $name The name of the data
     * @param mixed $default The value to return if there's no such data
     *
     * @return mixed
     */
    public function get(string $name, mixed $default = null): mixed
    {
        if ($this->has($name)) {
            return $this->data[$name];
        }

        return $default;
    }

    /**
     * Set the value of the given data
     *
     * @param string $name The name of the data
     * @param mixed $value
     *
     * @return $this
     */
    public function set(string $name, mixed $value): static
    {
        $this->data[$name] = $value;

        return $this;
    }

    /**
     * Merge the given data
     *
     * @param Data $with
     *
     * @return $this
     */
    public function merge(self $with): static
    {
        $this->data = array_merge($this->data, $with->data);

        return $this;
    }

    /**
     * Clear all data
     *
     * @return $this
     */
    public function clear(): static
    {
        $this->data = [];

        return $this;
    }
}
