<?php

namespace ipl\Web\Compat;

use ipl\Html\Contract\FormSubmitElement;
use ipl\Html\Form;
use ipl\Html\FormElement\SubmitElement;
use ipl\Html\HtmlDocument;
use ipl\Html\HtmlString;
use ipl\I18n\Translation;
use ipl\Web\FormDecorator\IcingaFormDecorator;

class CompatForm extends Form
{
    use Translation;

    protected $defaultAttributes = ['class' => 'icinga-form icinga-controls'];

    /**
     * Render the content of the element to HTML
     *
     * A duplicate of the primary submit button is being prepended if there is more than one present
     *
     * @return string
     */
    public function renderContent(): string
    {
        if (count($this->submitElements) > 1) {
            return (new HtmlDocument())
                ->setHtmlContent(
                    $this->duplicateSubmitButton($this->submitButton),
                    new HtmlString(parent::renderContent())
                )
                ->render();
        }

        return parent::renderContent();
    }

    public function hasDefaultElementDecorator()
    {
        if (parent::hasDefaultElementDecorator()) {
            return true;
        }

        $this->setDefaultElementDecorator(new IcingaFormDecorator());

        return true;
    }

    /**
     * Return a duplicate of the given submit button with the `class` attribute fixed to `primary-submit-btn-duplicate`
     *
     * @param FormSubmitElement $originalSubmitButton
     *
     * @return SubmitElement
     */
    public function duplicateSubmitButton(FormSubmitElement $originalSubmitButton): SubmitElement
    {
        $attributes = (clone $originalSubmitButton->getAttributes())
            ->set('class', 'primary-submit-btn-duplicate');
        $attributes->remove('id');

        return new SubmitElement($originalSubmitButton->getName(), $attributes);
    }
}
