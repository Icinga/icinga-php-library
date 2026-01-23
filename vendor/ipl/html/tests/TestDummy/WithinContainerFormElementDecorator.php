<?php

namespace ipl\Tests\Html\TestDummy;

use ipl\Html\BaseHtmlElement;
use ipl\Html\Contract\FormElement;
use ipl\Html\Contract\FormElementDecorator;
use ipl\Html\Html;

class WithinContainerFormElementDecorator extends BaseHtmlElement implements FormElementDecorator
{
    protected $tag = 'div';

    protected $defaultAttributes = ['class' => 'within-container-decorator'];

    /** @var FormElement */
    protected $formElement;

    public function decorate(FormElement $formElement)
    {
        $decorator = new static();
        $decorator->formElement = $formElement;

        $formElement->prependWrapper($decorator);

        return $decorator;
    }

    protected function assemble()
    {
        $this->add(Html::tag('div', ['class' => 'container'], $this->formElement));
    }
}
