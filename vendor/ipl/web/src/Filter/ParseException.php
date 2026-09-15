<?php

namespace ipl\Web\Filter;

use Exception;

class ParseException extends Exception
{
    protected $char;

    protected $charPos;

    public function __construct($filter, $char, $charPos, $extra)
    {
        parent::__construct(sprintf(
            'Invalid filter "%s", unexpected %s at pos %d%s',
            $filter,
            $char,
            $charPos,
            $extra
        ));

        $this->char = $char;
        $this->charPos = $charPos;
    }

    public function getChar()
    {
        return $this->char;
    }

    public function getCharPos()
    {
        return $this->charPos;
    }
}
