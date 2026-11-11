<?php

namespace ipl\Orm\Common;

use Closure;
use Traversable;
use ipl\Stdlib\Properties;

trait PropertiesWithDefaults
{
    use Properties {
        Properties::getProperty as private parentGetProperty;
    }

    protected function getProperty(string $key): mixed
    {
        if (isset($this->properties[$key]) && $this->properties[$key] instanceof Closure) {
            $this->setProperty($key, $this->properties[$key]($this, $key));
        }

        return $this->parentGetProperty($key);
    }

    public function getIterator(): Traversable
    {
        foreach ($this->properties as $key => $value) {
            if (! $value instanceof Closure) {
                yield $key => $value;
            }
        }
    }
}
