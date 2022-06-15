<?php

namespace ipl\Orm\Common;

use Closure;
use Traversable;

trait PropertiesWithDefaults
{
    use \ipl\Stdlib\Properties {
        \ipl\Stdlib\Properties::getProperty as private parentGetProperty;
    }

    protected function getProperty($key)
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
