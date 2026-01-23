<?php

namespace ipl\Tests\Html\TestDummy;

class ObjectThatCanBeCastedToString
{
    public function __toString()
    {
        return 'Some String <:-)';
    }
}
