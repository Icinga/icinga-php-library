<?php

namespace ipl\Web\Widget;

use ipl\Html\BaseHtmlElement;
use ipl\Html\Html;

class HorizontalKeyValue extends BaseHtmlElement
{
    protected $key;

    protected $value;

    protected $defaultAttributes = ['class' => 'horizontal-key-value'];

    protected $tag = 'div';

    public function __construct($key, $value)
    {
        $this->key = $key;
        $this->value = $value;
    }

    protected function assemble()
    {
        $this->add([
            Html::tag('div', ['class' => 'key'], $this->key),
            Html::tag('div', ['class' => 'value'], $this->value)
        ]);
    }
}
