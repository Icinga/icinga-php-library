<?php

namespace ipl\Stdlib;

class Data
{
    /** @var array */
    protected $data = [];

    /**
     * Check whether there's any data
     *
     * @return bool
     */
    public function isEmpty()
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
    public function has($name)
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
    public function get($name, $default = null)
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
    public function set($name, $value)
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
    public function merge(self $with)
    {
        $this->data = array_merge($this->data, $with->data);

        return $this;
    }

    /**
     * Clear all data
     *
     * @return $this
     */
    public function clear()
    {
        $this->data = [];

        return $this;
    }
}
