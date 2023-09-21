<?php

namespace ipl\Html\FormDecorator;

use Closure;
use ipl\Html\Contract\FormElement;
use ipl\Html\Contract\FormElementDecorator;
use ipl\Html\HtmlDocument;

class CallbackDecorator extends HtmlDocument implements FormElementDecorator
{
    /** @var Closure The decorating callback */
    protected $callback;

    /** @var FormElement The decorated form element */
    protected $formElement;

    /**
     * Create a new CallbackDecorator
     *
     * @param Closure $callback
     */
    public function __construct(Closure $callback)
    {
        $this->callback = $callback;
    }

    public function decorate(FormElement $formElement)
    {
        $decorator = clone $this;

        $decorator->formElement = $formElement;

        $formElement->prependWrapper($decorator);
    }

    protected function assemble()
    {
        call_user_func($this->callback, $this->formElement, $this);
    }
}
