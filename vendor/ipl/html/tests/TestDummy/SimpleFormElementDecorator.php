<?php

namespace ipl\Tests\Html\TestDummy;

use ipl\Html\BaseHtmlElement;
use ipl\Html\Contract\FormElement;
use ipl\Html\Contract\FormElementDecorator;

class SimpleFormElementDecorator extends BaseHtmlElement implements FormElementDecorator
{
    protected $tag = 'div';

    protected $defaultAttributes = ['class' => 'simple-decorator'];

    /** @var FormElement */
    protected $formElement;

    public function decorate(FormElement $formElement)
    {
        $decorator = new static();
        $decorator->formElement = $formElement;

        $formElement->prependWrapper($decorator);

        return $decorator;
    }
}
