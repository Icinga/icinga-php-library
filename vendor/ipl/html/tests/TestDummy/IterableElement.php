<?php

namespace ipl\Tests\Html\TestDummy;

use ArrayIterator;
use ipl\Html\Html;
use ipl\Html\ValidHtml;
use IteratorAggregate;
use Traversable;

class IterableElement implements IteratorAggregate, ValidHtml
{
    protected $content = ['foo', 'bar'];

    public function getIterator(): Traversable
    {
        return new ArrayIterator($this->content);
    }

    public function render()
    {
        return Html::wrapEach($this, 'b');
    }
}
