<?php

namespace ipl\Web\Compat\FormDecorator;

use ipl\Html\Contract\FormElement;
use ipl\Html\Contract\HtmlElementInterface;
use ipl\Html\HtmlDocument;
use ipl\Html\HtmlElement;
use ipl\Html\FormDecoration\DescriptionDecorator as iplHtmlDescriptionDecorator;
use ipl\Html\Text;
use ipl\Html\ValidHtml;
use ipl\Web\Widget\Icon;

/**
 * Decorates the description of the form element
 */
class DescriptionDecorator extends iplHtmlDescriptionDecorator
{
    protected string|array $class = 'sr-only';

    protected function getElementDescription(FormElement $formElement): HtmlElementInterface & ValidHtml
    {
        $description = $formElement->getDescription();
        $icon = new Icon('info-circle', [
            'aria-hidden'   => 'true',
            'class'         => 'control-info',
            'role'          => 'img',
            'title'         => $description
        ]);

        return (new HtmlElement('span', content: new Text($description)))
            ->addWrapper((new HtmlDocument())->addHtml($icon));
    }
}
