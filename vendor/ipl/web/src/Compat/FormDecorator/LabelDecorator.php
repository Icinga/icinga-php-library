<?php

namespace ipl\Web\Compat\FormDecorator;

use ipl\Html\Contract\DecorationResult;
use ipl\Html\Contract\Form;
use ipl\Html\Contract\FormElement;
use ipl\Html\Contract\FormDecoration;
use ipl\Html\Contract\MutableHtml;
use ipl\Html\HtmlDocument;
use ipl\Html\HtmlString;
use ipl\Html\FormDecoration\LabelDecorator as IplHtmlLabelDecorator;
use ipl\Html\ValidHtml;
use ipl\Html\Attributes;
use ipl\Html\HtmlElement;
use ipl\I18n\Translation;
use ipl\html\Text;

class LabelDecorator extends IplHtmlLabelDecorator implements FormDecoration
{
    use Translation;

    /**
     * @var bool Whether an explanation for the asterisk of required fields needs to be added
     */
    protected bool $requiredExplanationNeeded = false;

    /**
     * Decorates the label of the form element and adds a tooltip if it is required
     */
    protected function getElementLabel(FormElement $formElement): ?ValidHtml
    {
        $result = parent::getElementLabel($formElement);
        if ($result === null) {
            $result = HtmlString::create('&nbsp;');
        } elseif ($result instanceof MutableHtml) {
            if ($formElement->isRequired()) {
                $formElement->setAttribute('aria-required', 'true');
                $requiredHint = new HtmlElement(
                    'span',
                    Attributes::create([
                        'class' => 'required-hint',
                        'aria-hidden' => 'true',
                        'title' => $this->translate('Required')
                    ]),
                    Text::create(" *")
                );
                $this->requiredExplanationNeeded = true;
                $result->addHtml($requiredHint);
            }
        }

        return $result;
    }

    /**
     * Appends an explanation of the asterisk for required fields if at least one such field exists
     */
    public function decorateForm(DecorationResult $result, Form $form): void
    {
        if ($this->requiredExplanationNeeded) {
            $requiredExplanation = new HtmlElement(
                'ul',
                Attributes::create(['class' => 'form-info',]),
                new HtmlElement(
                    'li',
                    null,
                    Text::create(sprintf($this->translate('%s Required field'), '*'))
                )
            );
            $result->append($requiredExplanation);
        }
    }
}
